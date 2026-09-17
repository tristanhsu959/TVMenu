<?php

namespace App\Services\MonthlyFilling;

use App\Facades\AppManager;
use App\Facades\PurchaseManager;
use App\Facades\StoreManager;
use App\Facades\LocalLegacyManager;
use App\Repositories\MonthlyFillingRepository;
use App\Libraries\ResponseLib;
use App\Enums\Brand;
use App\Enums\Functions;
use App\Enums\Area;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Carbon\CarbonPeriod;
use Exception;
use OpenSpout\Writer\Common\Creator\WriterEntityFactory;
use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;

#partial Service
class FactoryService
{
	private $_statistics	= [];
   
	public function __construct(protected MonthlyFillingRepository $_repository)
	{
		$this->_statistics = [
			'type'			=> '',
			'range'			=> '',
			'brandId'		=> '', #export
			'startDate'		=> '', #Y-m-d
            'endDate'   	=> '',
			'header'		=> [],
			'data'			=> [],
			'exportName'	=> '', #export
			'exportToken'	=> '', #export
		];
	}
	
	/* ====================== 主流程 ====================== */
	/* Search data(因月初報表較固定模式,寫法不同)
	 * @params: int
	 * @params: date
	 * @params: date
	 * @params: string
	 * @params: string
	 * @return: array
	 */
	public function analysis($params)
	{
		try
		{
			#Prepare data(object default called by reference)
			$this->_prepareData($params);
				
			#Statistics
			$this->_outputReport($params);
				
			#Create output to var statistics
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
		$this->_statistics['type']			= $params->type;
		$this->_statistics['range']			= $params->range;
		$this->_statistics['brandId']		= $params->brand->value;
		$this->_statistics['brandCode']		= $params->brand->code();
		$this->_statistics['startDate'] 	= $params->stDate;
		$this->_statistics['endDate']		= $params->endDate;
		$this->_statistics['header']		= $params->header;
		$this->_statistics['data']			= $params->data;
		$this->_statistics['hasResult'] 	= FALSE;
		
		#無值不cache
		if (! empty(Arr::flatten($this->_statistics['data'])))
		{
			$this->_statistics['hasResult'] 	= TRUE;
			$this->_statistics['exportName']	= '各餡月均量';
			$this->_statistics['exportToken'] 	= bin2hex($params->cacheKey); #hex2bin
			Cache::put($params->cacheKey, $this->_statistics, now()->addMinutes(10));
		}
	}
	
	/* 取統計相關參數
	 * @params: enums
	 * @params: integer
	 * @return: array
	 */
	private function _prepareData($params)
	{
		try
		{
			#1.Get product id
			$this->_getProductIdByCode($params);
			
			#2.Build params
			$this->_getFactoryList($params);
			
			
			#3.Get store
			#因追加的關係才抓storeList
			$this->_getStoreList($params);
			
			#4.Get Purchase data
			$orderData = $this->_getDataFromDB($params);
			
			#5.Get extra data
			$extraData = $this->_getExtraDataFromDB($params);
			
			#6. Build base data
			#會有false的無效array, 用array_filter去除
			$this->_buildBaseData($params, array_filter($orderData), array_filter($extraData));
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception($e->getMessage());
		}
	}
	
	/* Short code to proudct id
	 * @params: int
	 * @return: array
	 */
	private function _getProductIdByCode($params)
	{
		try
		{
			$brand				= $params->brand;
			$productList		= config('web.purchase.monthly_filling.monthly');
			$allowOpCenterIds 	= $params->allowOpCenterIds;
			
			#非0開頭會變成int(sql會convert error)
			$codes 	= collect($productList)->pluck('code')->all();
			
			#取norder DB對應的product id
			$ids = PurchaseManager::getProductIdByShortCode($brand, $allowOpCenterIds, $codes);
			
			if (empty($ids))
				throw new Exception('查無參照的產品');
			
			$params->productList	= $productList;
			$params->productCodes 	= $codes; #舊系統DB需用到
			$params->productIds 	= $ids;
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception($e->getMessage());
		}
	}
	
	/* Get order data
	 * @params: enums
	 * @params: date
	 * @params: date
	 * @params: array
	 * @return: array
	 */
	private function _getFactoryList($params)
	{
		try
		{
			#代授權OpCenter,若要取全部工廠則代入全部
			$factory 	= PurchaseManager::getFactoryList($params->brand, $params->allowOpCenterIds);
			
			$params->factoryList = $factory;
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('讀取出貨工廠資料失敗');
		}
	}
	
	/* 門店資料
	 * @params: collection
	 * @return: array
	 */
	private function _getStoreList($params)
	{
		$brand 				= $params->brand;
		$stDate				= $params->stDate;
		$endDate			= $params->endDate;
		$allowOpCenterIds 	= $params->allowOpCenterIds;
		$allowAreaIds 		= $params->allowAreaIds;
		
		$storeList = StoreManager::getStoreListWithLb($brand, $allowOpCenterIds, $allowAreaIds, $stDate, $endDate);
		#這裏不排除工廠學區店
		#$params->storeList = StoreManager::filterFactoryStore($brand, $storeList);
		
		$params->storeList = $storeList;
	}
	
	/* Get order data
	 * @params: array
	 * @return: array
	 */
	private function _getDataFromDB($params)
	{
		/* array:4 [
			"expectedDate" => "2026-02"
			"qty" => "7059"
			"factoryNo" => "TW_TP" | TW_KH
			"shortCode" => "2267"
		]
		*/
	
		try
		{
			$brand 				= $params->brand;
			$stDate				= (new Carbon($params->stDate))->format('Y-m-d 00:00:00');
			$endDate 			= (new Carbon($params->endDate))->addDay()->format('Y-m-d H:i:s');
			$productIds 		= $params->productIds;
			$allowOpCenterIds 	= $params->allowOpCenterIds;
			$allowAreaIds 		= $params->allowAreaIds;
			
			$orderData = $this->_repository->getOrderDataByFactory($brand, $allowOpCenterIds, $allowAreaIds, $stDate, $endDate, $productIds);
			
			return $orderData;
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('讀取訂貨系統訂單資料失敗');
		}
	}
	
	/* Get extra order data from old system
	 * @params: 
	 * @return: array
	 */
	private function _getExtraDataFromDB($params)
	{
		try
		{
			$brand 				= $params->brand;
			$stDate				= (new Carbon($params->stDate))->format('Y-m-d 00:00:00');
			$endDate 			= (new Carbon($params->endDate))->addDay()->format('Y-m-d H:i:s');
			$productCodes 		= $params->productCodes;
			$allowOpCenterIds 	= $params->allowOpCenterIds;
			$allowAreaIds 		= $params->allowAreaIds;
			
			$extraData = LocalLegacyManager::getExtraDataByProduct($brand, $allowOpCenterIds, $stDate, $endDate, $productCodes);
			
			#因無areaId, 故只能從門店過濾
			$validStoreKeys = collect($params->storeList)->pluck('storeKey')->values()->all();
			
			$extraData = collect($extraData)->filter(function($item, $key) use($validStoreKeys) {
				$storeKey = Str::take($item['storeNo'], 7);
				return in_array($storeKey, $validStoreKeys);
			})->toArray();
			
			return $extraData;
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('讀取訂貨系統訂單資料失敗');
		}
	}
	
	/* 基底資料
	 * @params: collection
	 * @return: array
	 */
	private function _buildBaseData($params, $orderData, $extraData)
	{
		#整合追加資料
		$baseData = collect($orderData)->merge($extraData); 
		
		#處理包裝轉換
		$baseData = $baseData->map(function($item, $key){
			$temp['expectedDate'] = Carbon::parse($item['expectedDate'])->format('Y-m');
			$temp['factoryNo'] = $item['factoryNo'];
			$temp['shortCode'] = $item['shortCode'];
			
			$temp['qty'] = round(intval($item['qty']) * PurchaseManager::getPackagingScale($item['shortCode']), 2);
			
			return $temp;
		})->toArray();
		
		$params->baseData = $baseData;
	}
	/* ====================== 主流程 End ====================== */
	
	
	/* ========================== 統計 ========================== */
	/* ========================================================== */
	/* 
	 * @params: array
	 * @return: array
	 */
	private function _outputReport($params)
	{
		try
		{
			#1.計算查詢範圍Month
			$this->_buildMonthRange($params);
			
			#2.統計
			$this->_parsingByFactory($params);
			
			#3.產出成row data
			$params->set('data.qty', $this->_generateOutput($params, 'qty'));
			$params->set('data.avg', $this->_generateOutput($params, 'avg'));
			
			return $params;
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('解析報表資料發生錯誤');
		}
	}
	
	/* 計算用參數
	 * @params: 
	 * @return: array
	 */
	private function _buildMonthRange($params)
	{
		$monthList 	= [];
		
		#By month
		$st 	= Carbon::parse($params->stDate)->startOfMonth();
		$end	= Carbon::parse($params->endDate)->startOfMonth();
			
		$period = CarbonPeriod::create($st, '1 month', $end);
		foreach ($period as $month) 
		{
			$monthList[] = $month->format('Y-m');
		}
		
		$params->monthList = $monthList;
	}
	
	/* 依工廠
	 * @params: array
	 * @return: array
	 */
	private function _parsingByFactory($params)
	{
		/*
		[
			"TW_KH" => array:2 [
				"2026-01" => array:9 [
					"0001" => array:2 [
						"qty" => 464490
						"avg" => 14983.55
					]
					"0002" => array:2 [...]
					"0003" => array:2 [...]
					"0005" => array:2 [...]
					"0006" => array:2 [...]
					"0007" => array:2 [...]
					"0011" => array:2 [...]
					"0015" => array:2 [...]
					"2267" => array:2 [...]
				]
				"2026-02" => array:9 [..]
			]
			"TW_TP" => array:2 [...]
		*/
		$orderData = $params->baseData;
		
		if (empty($orderData))
			return [];
		
		#分群定義不同
		$result = collect($orderData)->groupBy('factoryNo')->map(function($items, $key) {
			return $items->groupBy('expectedDate')->map(function($items, $month) {
				return $items->groupBy('shortCode')->map(function($items, $key) use ($month){
					
					$days = Carbon::parse($month)->daysInMonth;
					$temp['qty'] = round($items->pluck('qty')->sum(), 2);
					$temp['avg'] = round($temp['qty'] / $days, 2);
					
					return $temp;
					
				})->toArray();
			});
			
		})->sortKeys()->toArray();
		
		$params->parsingData = $result;
	}
	
	/* 改成產出row data
	 * @params: array
	 * @return: array
	 */
	private function _generateOutput($params, $type)
	{
		$parsingData = $params->parsingData;
		
		if (empty($parsingData))
			return [];
		
		$productList	= collect($params->productList)->pluck('name', 'code')->toArray();
		$factoryList	= $params->factoryList;
		$monthList		= $params->monthList;
		
		$params->header = array_merge(['出貨工廠', '年月'], array_values($productList));
		
		#共用Header
		$rowData = [];
		
		foreach($factoryList as $factoryNo => $factoryName)
		{
			$factoryData = data_get($parsingData, $factoryNo);
			
			if (empty($factoryData))
				continue;
			
			foreach($monthList as $month)
			{
				$row = [];
				$row[] = $factoryName;
				$row[] = $month;
				
				foreach($productList as $code => $name)
				{
					$row[] = data_get($factoryData, "{$month}.{$code}.{$type}", 0);
				}
				
				$rowData[] = $row;
			}
		}
		
		return $rowData;
	}
	
	/* Export data
	 * @params: array
	 * @return: array
	 */
	public function export($sourceData)
	{
		try
		{
			#Build export data for sheets
			$export = $this->_buildExportData($sourceData['header'], $sourceData['data']);
			
			#Write export to file
			$brandName = Brand::tryFrom($sourceData['brandId'])->label();
			$fileName = Str::replaceArray('?', [$brandName, $sourceData['exportName'], $sourceData['startDate'], $sourceData['endDate']], '?_月初報表_?_?_?.xlsx');
			$filePath = Storage::disk('export')->path($fileName);
			
			$writer = new Writer();
			$writer->openToFile($filePath);
			
			$index = 0;
			foreach($export as $sheetKey => $sheetData)
			{
				$sheet = ($index == 0) ? $writer->getCurrentSheet() : $writer->addNewSheetAndMakeItCurrent();
				$sheetName = ($sheetKey == 'qty') ? '月總量' : '月均量';
				$sheet->setName($sheetName);
				
				foreach($sheetData as $data)
				{
					$row =  Row::fromValues($data);
					$writer->addRow($row);
				}
				$index++;
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
	private function _buildExportData($header, $data)
	{
		#Header row
		$export['qty'][] = $header;
		$export['avg'][] = $header;
		
		#只需要$key值
		foreach($export as $key => &$row)
		{
			foreach($data[$key] as $rowData)
			{
				$row[] = $rowData;
			}
		}
		
		return $export;
	}
}
