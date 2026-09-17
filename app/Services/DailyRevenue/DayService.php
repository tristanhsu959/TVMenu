<?php

namespace App\Services\DailyRevenue;

use App\Facades\AppManager;
use App\Facades\PosManager;
use App\Facades\StoreManager;
use App\Repositories\DailyRevenueRepository;
use App\Libraries\ResponseLib;
use App\Libraries\Sales\AreaLib;
use App\Enums\OpCenter;
use App\Enums\Brand;
use App\Enums\Functions;
use App\Enums\Area;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Number;
use Carbon\CarbonPeriod;
use Exception;
use OpenSpout\Writer\Common\Creator\WriterEntityFactory;
use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;

#partial Service
class DayService
{
	private $_statistics	= [];
   
	public function __construct(protected DailyRevenueRepository $_repository)
	{
		$this->_statistics = [
			'brandId'		=> '', #export
			'brandCode'		=> '',
			'type'			=> '',
			'calc'			=> [],
			'startDate'		=> '', #Y-m-d
            'endDate'   	=> '',
			'hasHourlyData' => FALSE,
			'hasClosingData'=> FALSE,
			'store' 		=> [],
			'exportName'	=> '門店營收_單日',
			'exportToken'	=> '', #export
		];
	}
	
	/* ====================== 主流程 By Name ====================== */
	/* Search data
	 * @params: array
	 * @return: array
	 */
	public function analysis($params)
	{
		try
		{
			$this->_prepareData($params);
			
			$this->_outputReport($params);
			
			$this->_generateStatistics($params);
			
			return $this->_statistics;
		}
		catch(Exception $e)
		{
			throw new Exception($e->getMessage());
		}
	}
	
	/* Generate statistics data
	 * @params: object
	 * @return: array
	 */
	private function _generateStatistics($params)
	{
		$this->_statistics['brandId']		= $params->brand->value;
		$this->_statistics['brandCode']		= $params->brand->code();
		$this->_statistics['type']			= $params->type;
		$this->_statistics['calc']			= $params->calc;
		$this->_statistics['startDate'] 	= $params->stDate;
		$this->_statistics['endDate']		= $params->endDate;
		$this->_statistics['store']			= $params->store;
		$this->_statistics['hasResult']		= FALSE;
		$this->_statistics['hasHourlyData']	= $params->hasHourlyData;
		$this->_statistics['hasClosingData']= $params->hasClosingData;
		
		#無值不cache,因有補全門店,故hasResult應該都是TRUE
		if (! empty(Arr::flatten($this->_statistics['store']['data'])))
		{
			$this->_statistics['hasResult']		= TRUE;
			$this->_statistics['exportToken'] 	= bin2hex($params->cacheKey); #hex2bin
			Cache::put($params->cacheKey, $this->_statistics, now()->addMinutes(10));
		}
	}
	
	/* ====================== 主流程 End ====================== */
	
	/* Get search data
	 * @params: array
	 * @return: array
	 */
	private function _prepareData($params)
	{
		try
		{
			#1. Get Store
			$this->_getStoreList($params);
			
			#2.過濾查詢店名
			$this->_getPosIdByName($params);
			
			#3. Get data from DB(銷售)
			$this->_getSaleDataFromDBWithHourly($params);
			
			#4. Get data from DB(日結)
			$this->_getDailyClosingDataFromDB($params);
			
			#5.build to base data
			$this->_buildBaseData($params);
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception($e->getMessage());
		}
	}
	
	/* 門店資料
	 * @params: collection
	 * @return: array
	 */
	private function _getStoreList($params)
	{
		#20260701:改用訂貨門店來mapping
		#改Mapping訂貨門店-已判別日期, 等同active list
		$brand 				= $params->brand;
		$allowOpCenterIds 	= $params->allowOpCenterIds;
		$allowAreaIds 		= $params->allowAreaIds;
		$stDate				= $params->stDate;
		$endDate			= $params->endDate;
		
		$storeList = StoreManager::getStoreList($brand, $allowOpCenterIds, $allowAreaIds, $stDate, $endDate);
		$storeList = PosManager::filterSpecialStore($brand, $storeList);
		
		#因storeList已是有效門店, 改為由門店關聯資料
		$storeList = StoreManager::filterStoreByTypeName($storeList, $params->storeType);
		
		$params->storeList = StoreManager::filterFactoryStore($brand, $storeList);
	}
	
	/* 取查詢的門店資料
	 * @params: collection
	 * @return: array
	 */
	private function _getPosIdByName($params)
	{
		#避免用like查詢
		$storeName = $params->storeName;
		
		if (empty($storeName))
		{
			$params->namePosIds = [];
			return TRUE;
		}
		
		$storeList = collect($params->storeList)->filter(function($item, $key) use($storeName){
			return Str::contains($item['storeName'], $storeName);
		});
		
		#有查店名,要更新store list
		$params->storeList 	= $storeList->toArray();
		$params->namePosIds	= $storeList->pluck('posId')->values()->all(); 
		
		if (empty($params->storeList))
			throw new Exception('查無符合門店資料');
	}
	
	
	/* Get buy good data
	 * @params: fluent
	 * @return: array
	 */
	private function _getSaleDataFromDBWithHourly($params)
	{
		/*[
			"shopId" => "0103"
			"saleDateHour" => "2026-07-20 00"
			"amount" => "5688.0000"
			"totalSales" => "5688.0000"
			"totalExtra" => ".0000"
			"totalDischarge" => ".0000"
		]*/
  
		try
		{
			$brand 			= $params->brand;
			$stDate			= (new Carbon($params->stDate))->format('Y-m-d 00:00:00');
			$endDate 		= (new Carbon($params->endDate))->addDay()->format('Y-m-d H:i:s');
			$storeType 		= $params->storeType;
			$namePosIds 	= $params->namePosIds;
			$allowAreaIds 	= $params->allowAreaIds;
			
			$result = $this->_repository->getFromSale00WithHourly($brand, $allowAreaIds, $stDate, $endDate, $storeType, $namePosIds);
			
			$params->saleData = array_filter($result);
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('讀取POS系統訂單資料失敗');
		}
	}
	
	/* Get buy good data
	 * @params: fluent
	 * @return: array
	 */
	private function _getDailyClosingDataFromDB($params)
	{
		/*[
			"shopId" => "0103"
			"amount" => "106385.0000"
			"saleDate" => "2026-07-20 00:03:12.697"
		]*/
		
		try
		{
			if ($params->hasClosingData === FALSE)
			{
				$params->closingData = [];
				return TRUE;
			}
			
			$brand 			= $params->brand;
			$stDate			= (new Carbon($params->stDate))->format('Y-m-d 00:00:00');
			$endDate 		= (new Carbon($params->endDate))->addDay()->format('Y-m-d H:i:s');
			$storeType 		= $params->storeType;
			$namePosIds 	= $params->namePosIds;
			$allowAreaIds 	= $params->allowAreaIds;
			
			#因無相關欄位, 先全抓再由門店過濾
			#日結當天不會有資料(要取input_date)
			$result = $this->_repository->getFromDailyClosing($brand, $allowAreaIds, $stDate, $endDate, $storeType, $namePosIds);
			
			$params->closingData = array_filter($result);
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('讀取POS系統訂單資料失敗');
		}
	}
	
	/* 基底資料(DB已計算Sum)
	 * @params: collection
	 * @return: array
	 */
	private function _buildBaseData($params)
	{
		#先整理Data但不總計, 後續再處理mapping
		$saleData = collect($params->saleData)->map(function($item, $key){
			
			$temp['shopId']	= $item['shopId'];
			
			#DB: 00表00:00~00:59 => 歸到01,故要先加1hr
			
			$temp['hour'] = Carbon::createFromFormat('Y-m-d H', $item['saleDateHour'])->format('H');
			#先不加1hr=>Carbon::createFromFormat('Y-m-d H', $item['saleDateHour'])->addHour()->format('H');
			
			#發票金額 ＝ amount = totalSales + totalDischarge(因totalSales有可能0)
			$amount = floatval($item['amount']);
			
			#實銷金額
			$temp['totalSales']		= floatval($item['totalSales']);
			#溢收
			$temp['totalExtra']		= floatval($item['totalExtra']);
			#折讓
			$temp['totalDischarge']	= floatval($item['totalDischarge']);
			#實收金額
			$temp['totalAmount'] 	= $temp['totalSales'] + $temp['totalExtra']	+ $temp['totalDischarge'];
			#發票金額
			$temp['invoiceAmount'] 	= empty($temp['totalSales']) ? $amount : $temp['totalSales'] + $temp['totalDischarge'];
			
			return $temp;
		})->groupBy('shopId')->toArray();
		
		#saleBaseData
		$params->set('baseData.sales', $saleData);
		
		
		#日結-理論上只有一筆, key-value
		$closingData = collect($params->closingData)->groupBy('shopId')->map(function($items, $key){
			return floatval($items->pluck('amount')->sum());
		})->toArray();
		
		$params->set('baseData.closing', $closingData);
	}
	
	/* ========================== 統計 ========================== */
	/* ========================================================== */
	/* 取使用者可讀取區域資料(原主邏輯不動)
	 * @params: array
	 * @return: array
	 */
	private function _outputReport($params)
	{
		try
		{
			#1.Header(共用)
			$this->_buildHourRange($params);
			
			#2.By店別日營收
			$this->_parsingByStore($params);
			
			return $params;
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('解析報表資料發生錯誤');
		}
	}
	
	/* 計算日期天數
	 * @params: 
	 * @return: array
	 */
	private function _buildHourRange($params)
	{
		#hour都一樣, 不用用stDate去算
		$period = CarbonPeriod::create(Carbon::today(), '1 hour', Carbon::today()->setHour(23)); #Carbon::tomorrow()
		
		$hourList = [];

		foreach ($period as $date) 
		{
			$key = intval($date->format('H'));
			$hourList[] = $date->isTomorrow() ? '24:00' : $date->format('H:i');
		}
		
		$params->hourRange = $hourList;
	}
	
	/* 店別每日營收
	 * @params: array
	 * @return: array
	 */
	private function _parsingByStore($params)
	{
		
		$params->set('store.header', []);
		$params->set('store.data', []);
		
		$saleData 		= $params->baseData['sales'];
		$closingData 	= ($params->hasClosingData) ? $params->baseData['closing'] : [];
		$hourRange		= $params->hourRange;
		$hasHourlyData	= $params->hasHourlyData;
		
		#會有無設定區域權限的狀況, 須判別
		if (empty($saleData))
			return TRUE;
		
		$header = ['區域', 'Pos店號', '門店代號', '門店名稱', '實銷金額', '溢收金額', '折讓金額', '實收金額', '發票金額'];
		
		if ($params->hasClosingData)
			$header[] = '日結金額';
		
		$params->set('store.header', $header);
		
		$result = collect($params->storeList)->map(function($item, $key) use($saleData, $closingData, $hourRange, $hasHourlyData){
			$sale 			= data_get($saleData, $item['posId'], []);
			$closingAmount	= data_get($closingData, $item['posId'], 0); #已計算處理過
			
			$temp['areaName'] 		= $item['areaName'];
			$temp['posId']			= $item['posId'];
			$temp['storeKey'] 		= $item['storeKey'];
			$temp['storeName']		= $item['storeName'];
			$temp['totalSales']		= round(collect($sale)->pluck('totalSales')->sum(), 2);
			$temp['totalExtra']		= round(collect($sale)->pluck('totalExtra')->sum(), 2);
			$temp['totalDischarge']	= round(collect($sale)->pluck('totalDischarge')->sum(), 2);
			$temp['totalAmount']	= round(collect($sale)->pluck('totalAmount')->sum(), 2);
			$temp['invoiceAmount']	= round(collect($sale)->pluck('invoiceAmount')->sum(), 2);
			$temp['closingAmount']	= round($closingAmount, 2);
			
			if ($hasHourlyData)
			{
				#DB:00~23, hourRange: 00~23, 顯示01~24:表示累積...
				$hourlyData = collect($sale)->mapWithKeys(function($item, $key){
					return [$item['hour'] => $item['totalAmount']];
				})->all();
				
				$temp['hourly']	= collect($hourRange)->mapWithKeys(function($timeStr, $key) use($hourlyData){
					
					$hourKey = Str::before($timeStr, ':');
					$amount = data_get($hourlyData, $hourKey, 0);
					
					#00:00 => 顯示成01:00要加1hr
					$mapKey = Carbon::createFromFormat('H:i', $timeStr)->format('H:i'); #->addHour()
					
					return [$mapKey => round($amount, 2)];
				})->all();
			}
			else
				$temp['hourly']	= [];	
			
			return $temp;
		})->values()->all();
		
		$params->set('store.data',  $result);
	}
	
	
	/*************** 匯出 ***************/
	/* Export data
	 * @params: array
	 * @return: array
	 */
	public function export($sourceData)
	{
		try
		{
			#Build export data for sheets
			$export = $this->_buildExportStore($sourceData);
			
			#Write export to file
			$brandName = Brand::tryFrom($sourceData['brandId'])->label();
			$fileName = Str::replaceArray('?', [$brandName, $sourceData['exportName'], $sourceData['startDate'], $sourceData['endDate']], '?_?_?_?.xlsx');
			$filePath = Storage::disk('export')->path($fileName);
			
			$writer = new Writer();
			$writer->openToFile($filePath);
			
			$index = 0;
			$sheet = $writer->getCurrentSheet();
			$sheet->setName($sourceData['exportName']);
				
			foreach($export as $data)
			{
				$row =  Row::fromValues($data);
				$writer->addRow($row);
			}
			
			$writer->close();
			return ResponseLib::initialize($fileName)->success();
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			return ResponseLib::initialize('檔案下載失敗，請重新查詢')->fail();
		}
	}
	
	
	/* Build data for export
	 * @params: array
	 * @return: array
	 */
	private function _buildExportStore($srcData)
	{
		$export = [];
		$export[] = $srcData['store']['header'];
		$hasClosingData = $srcData['hasClosingData'];
		
		foreach($srcData['store']['data'] as $store)
		{
			$row = [];
			$row[] = $store['areaName'];
			$row[] = $store['posId'];
			$row[] = $store['storeKey'];
			$row[] = $store['storeName'];
			$row[] = Number::currency($store['totalSales'], precision: 0);
			$row[] = Number::currency($store['totalExtra'], precision: 0);
			$row[] = Number::currency($store['totalDischarge'], precision: 0);
			$row[] = Number::currency($store['totalAmount'], precision: 0);
			$row[] = Number::currency($store['invoiceAmount'], precision: 0);
			
			if ($hasClosingData)
				$row[] = Number::currency($store['closingAmount'], precision: 0);
			
			$export[]= $row;
		}
		
		return $export;
	}
}
