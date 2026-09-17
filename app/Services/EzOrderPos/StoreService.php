<?php

namespace App\Services\EzOrderPos;

use App\Facades\AppManager;
use App\Facades\PosManager;
use App\Facades\StoreManager;
use App\Repositories\EzOrderPosRepository;
use App\Libraries\ResponseLib;
use App\Libraries\HelperLib;
use App\Enums\Brand;
use App\Enums\Functions;
use App\Enums\Area;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Fluent;
use Illuminate\Support\Number;
use Carbon\CarbonPeriod;
use Exception;
use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style; 
use OpenSpout\Common\Entity\Style\CellAlignment;

#partial Service
class StoreService
{
   
	public function __construct(protected EzOrderPosRepository $_repository)
	{
	}
	
	public function analysis($params)
	{
		try
		{
			$this->_prepareData($params);
			
			$this->_outputReport($params);
			
			return $this->_generateStatistics($params);
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
		$statistics['brandId']			= $params->brand->value;
		$statistics['brandCode']		= $params->brand->code();
		$statistics['type']				= $params->type;
		$statistics['by']				= $params->by;
		$statistics['startDate'] 		= $params->stDate;
		$statistics['endDate']			= $params->endDate;
		$statistics['store']['header']	= $params->header;
		$statistics['store']['data']	= $params->data;
		$statistics['hasResult']		= FALSE;
		$statistics['hasFilter']		= TRUE;
		
		#無值不cache
		if (! empty($statistics['store']['data']))
		{
			$statistics['exportToken'] = bin2hex($params->cacheKey); #hex2bin
			Cache::put($params->cacheKey, $statistics, now()->addMinutes(10));
			
			$statistics['hasResult']	= TRUE;
		}
		
		return $statistics;
	}
	
	/* ====================== Prepare Data ====================== */
	/* Get search data
	 * @params: array
	 * @return: array
	 */
	private function _prepareData($params)
	{
		try
		{
			#1. Get all shops with area permission
			$this->_getActiveStoreList($params);
			
			#2.過濾查詢店名
			$this->_getPosIdByName($params);
			
			#3. 八方點Area或查詢對應的POS ID
			$this->_getAreaPosIdForEzOrder($params);
			
			#4.八方點Data
			$this->_getEzorderFromDB($params);
			
			#5.POS Data
			$this->_getPosFromDB($params);
			
			#6.POS Data
			$this->_buildBaseData($params);
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception($e->getMessage());
		}
	}
	
	/* Get store info
	 * @params: fluent
	 * @return: array
	 */
	private function _getActiveStoreList($params)
	{
		#門店以訂貨的為基準, 因八方點是用訂貨的store
		#改Mapping訂貨門店-已判別日期, 等同active list
		$brand 				= $params->brand;
		$allowOpCenterIds 	= $params->allowOpCenterIds;
		$allowAreaIds 		= $params->allowAreaIds;
		$stDate				= $params->stDate;
		$endDate			= $params->endDate;
		
		#已過濾廠區學區店(含八方點定義的,無ezorder excepts, 因訂貨無此店號不用濾)
		$storeList = StoreManager::getStoreList($brand, $allowOpCenterIds, $allowAreaIds, $stDate, $endDate);
		$storeList = PosManager::filterSpecialStore($brand, $storeList);
		$storeList = StoreManager::filterEzorderFactoryStore($brand, $storeList);
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
			throw new Exception('查無符合資料');
	}
	
	/* Get posIds 八方點判別用
	 * @params: fluent
	 * @return: array
	 */
	private function _getAreaPosIdForEzOrder($params)
	{
		#因八方點無area, 故需用posid來判別過濾
		$allowAreaIds = $params->allowAreaIds;
		
		#門店已過濾區域權限及storeName
		$areaPosIds	= collect($params->storeList)->filter(function($item, $key) use($allowAreaIds){
			return in_array($item['areaId'], $allowAreaIds);
		})->pluck('posId')->toArray(); 
		
		#區域:只有八方點需要用到
		$params->areaPosIds = $areaPosIds;
	}
	
	/* 取八方點DB
	 * @params: enums
	 * @params: integer
	 * @return: array
	 */
	private function _getEzorderFromDB($params)
	{
		try
		{
			$brand 		= $params->brand;
			$stDate		= (new Carbon($params->stDate))->format('Y-m-d 00:00:00');
			$endDate 	= (new Carbon($params->endDate))->addDay()->format('Y-m-d H:i:s');
			$posIds		= collect($params->areaPosIds)->merge($params->namePosIds)->unique()->all(); #八方點須再合併
			
			$result = $this->_repository->getDataFromEzOrder($brand, $stDate, $endDate, $posIds);
			
			$params->ezorderData = $result;
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('讀取八方點訂單資料失敗');
		}
	}
	
	
	/* 取統計相關參數
	 * @params: enums
	 * @params: integer
	 * @return: array
	 */
	private function _getPosFromDB($params)
	{
		/* [
			"shopId" => "0001"
			"orderCount" => "137"
			"amount" => "26629.0000"
			"totalSales" => "26630.5000"
			"totalExtra" => ".0000"
			"totalDischarge" => "-1.5000"
			"businessDays" => "137"
		] */
  
		try
		{
			$brand 		= $params->brand;
			$stDate		= (new Carbon($params->stDate))->format('Y-m-d 00:00:00');
			$endDate 	= (new Carbon($params->endDate))->addDay()->format('Y-m-d H:i:s');
			$areaIds 	= $params->allowAreaIds;
			$posIds		= $params->namePosIds;
			
			#因八方點無法計算營業日, 故要另外取
			if ($params->type == 'ez') 
				$result = $this->_repository->getBusinessDays($brand, $stDate, $endDate, $areaIds, $posIds);
			else
				$result = $this->_repository->getDataFromPos($brand, $stDate, $endDate, $areaIds, $posIds);
			
			$params->posData = $result;
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('讀取Pos訂單資料失敗');
		}
	}
	
	/* 基底資料
	 * @params: collection
	 * @return: array
	 */
	private function _buildBaseData($params)
	{
		if (empty($params->posData))
			return;
		
		#要先整理門店一致以便與ezorder對齊(依訂貨門店)
		#因八方點已是訂貨門店, 故只要處理POS
		$storeList = collect($params->storeList)->mapWithKeys(function($item, $key){
			return [$item['posId'] => $item['storeKey']];
		});
		
		$baseData = collect($params->posData)->map(function($item, $key) use($storeList){
			$temp['storeKey'] 	= data_get($storeList, $item['shopId'], NULL);
			
			#廠區或無POS再過濾
			if (empty($temp['storeKey']))
				return '';
			
			#二取一因可能有空值
			#發票金額 = amount OR totalSales + totalDischarge
			#實銷金額 = totalSales + totalExtra + totalDischarge
			$amount 		= floatval(data_get($item, 'amount', 0));
			$totalSales 	= floatval(data_get($item, 'totalSales', 0)) + floatval(data_get($item, 'totalExtra', 0)) + floatval(data_get($item, 'totalDischarge', 0));
			$temp['amount'] 		= empty($amount) ? $totalSales : $amount;
			$temp['orderCount']		= data_get($item, 'orderCount', 0);
			$temp['businessDays']	= data_get($item, 'businessDays', 0);
			
			return $temp; 
		})->all();
		
		$params->posData = array_filter($baseData);
	}
	
	/* ====================== Prepare Data End ====================== */
	
	
	/* ========================== Output 統計 ========================== */
	/* 整併統計資料
	 * @params: fluent object
	 * @return: array
	 */
	private function _outputReport($params)
	{
		try
		{
			$this->_buildHeader($params);
			
			$this->_parsingByStore($params);
			
			return $params;
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('解析報表資料發生錯誤');
		}
	}
	
	/* Header資料
	 * @params: collection
	 * @return: array
	 */
	private function _buildHeader($params)
	{
		$header = [
			'storeKey' 		=> '門店代號', 
			'storeName'		=> '門店名稱', 
			'areaName'		=> '區域', 
			'businessDays'	=> '營業天數',
			'ezOrderCount'	=> '八方點訂單數',
			'ezAmount'		=> '八方點總金額',
			'posOrderCount'	=> 'POS訂單數',
			'posAmount'		=> 'POS總金額',
			'avgOrderValue'	=> '平均客單價',
			'avgDayAmount'	=> '平均日營收',
			'visitorPercent'=> '來客數佔比',
			'amountPercent'	=> '業績佔比'
		];
		
		if ($params->type == 'ez')
			$params->header = collect($header)->only(['storeKey', 'storeName', 'areaName', 'businessDays', 'ezOrderCount', 'ezAmount'])->values()->all();
		else if ($params->type == 'ezpos')
			$params->header = collect($header)->values()->all();
	}
	
	/* Parsing data
	 * @params: collection
	 * @return: array
	 */
	private function _parsingByStore($params)
	{
		if (empty($params->ezorderData) && empty($params->posData))
		{
			$params->data = [];
			return;
		}
		
		#合併data
		$ezorderData = collect($params->ezorderData)->mapWithKeys(function($item, $key){
			return [$item['storeKey'] => $item];
		});
		
		$posData = collect($params->posData)->mapWithKeys(function($item, $key){
			return [$item['storeKey'] => $item];
		});
		
		$type = $params->type;
		
		$data = collect($params->storeList)->map(function($item, $key) use($ezorderData, $posData, $type){
			
			$ezOrder	= data_get($ezorderData, $item['storeKey'], NULL);
			$posOrder 	= data_get($posData, $item['storeKey'], NULL);
			
			$temp['storeKey'] 		= $item['storeKey'];
			$temp['storeName'] 		= $item['storeName'];
			$temp['areaName'] 		= $item['areaName'];
			$temp['businessDays'] 	= intval(data_get($posOrder, 'businessDays', 0));
			
			#八方點
			$temp['ezOrderCount'] 	= intval(data_get($ezOrder, 'orderCount', 0));
			$temp['ezAmount'] 		= round(data_get($ezOrder, 'amount', 0), 2);
			
			#POS
			$temp['posOrderCount'] 	= intval(data_get($posOrder, 'orderCount', 0));
			$temp['posAmount'] 		= round(data_get($posOrder, 'amount', 0), 2);
			
			#總計
			$temp['avgOrderAmount'] = empty($temp['posOrderCount']) ? 0 : round($temp['posAmount'] / $temp['posOrderCount'], 2); #平均客單價
			$temp['avgDayAmount'] 	= empty($temp['businessDays']) ? 0 : round($temp['posAmount'] / $temp['businessDays'], 2);#平均每日營收
			
			#佔比
			$temp['countPercent'] 	= empty($temp['posOrderCount']) ? 0 : round($temp['ezOrderCount'] / $temp['posOrderCount'] * 100, 2) ; #來客數
			$temp['amountPercent'] 	= empty($temp['posAmount']) ? 0 : round($temp['ezAmount'] / $temp['posAmount'] * 100, 2);#營收
			
			$temp['ezAmount'] 		= Number::currency($temp['ezAmount'], precision: 2);
			$temp['posAmount'] 		= Number::currency($temp['posAmount'], precision: 2);
			$temp['avgOrderAmount'] = Number::currency($temp['avgOrderAmount'], precision: 2);
			$temp['avgDayAmount'] 	= Number::currency($temp['avgDayAmount'], precision: 2);
			$temp['countPercent'] 	= Number::percentage($temp['countPercent'], precision: 2); 
			$temp['amountPercent'] 	= Number::percentage($temp['amountPercent'], precision: 2); 
			
			if ($type == 'ez')
				return collect($temp)->only(['storeKey', 'storeName', 'areaName', 'businessDays', 'ezOrderCount', 'ezAmount'])->values()->all();
			else
				return collect($temp)->values()->all();
		})->all();
		
		$params->data = array_filter($data);
	}
	
	/* ========================== 統計 End ========================== */
	
	
	/* ========================== 匯出 ========================== */
	/* Export data
	 * @params: array
	 * @return: array
	 */
	public function export($sourceData)
	{
		try
		{
			#Build export data for sheets
			$export = $this->_buildExportData($sourceData);
			
			#Write export to file
			$brandName 	= Brand::tryFrom($sourceData['brandId'])->label();
			$typeName	= ($sourceData['type'] == 'ez') ? '八方點' : '八方點及銷售';
			$byName		= match($sourceData['by']) {
					'store'	=> '門店',
					'area'	=> '區域',
				};
			
			$fileName = Str::replaceArray('?', [$brandName, $typeName, $byName, $sourceData['startDate'], $sourceData['endDate']], '?_?統計_?_?_?.xlsx');
			$filePath = Storage::disk('export')->path($fileName);
			
			$writer = new Writer();
			$writer->openToFile($filePath);
			
			$sheet = $writer->getCurrentSheet();
			$sheet->setName("{$typeName}-{$byName}");
			
			$centerStyle = (new Style())->setCellAlignment(CellAlignment::CENTER);
			
			foreach($export as $data)
			{
				$row =  Row::fromValues($data, $centerStyle);
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
	private function _buildExportData($sourceData)
	{
		$export = [];
		$export[] = $sourceData['store']['header'];
		
		foreach($sourceData['store']['data'] as $data)
		{
			/* 
			$data['ezAmount'] 			= Number::currency($data['ezAmount'], precision: 2);
			$data['posAmount'] 			= Number::currency($data['posAmount'], precision: 2);
			$data['avgOrderAmount'] 	= Number::currency($data['avgOrderAmount'], precision: 2);
			$data['avgDayAmount'] 		= Number::currency($data['avgDayAmount'], precision: 2);
			$data['countPercent'] 		= Number::percentage($data['countPercent'], precision: 2); 
			$data['amountPercent'] 		= Number::percentage($data['amountPercent'], precision: 2); 
			*/
			$export[] = array_values($data);
		}
		
		return $export;
	}
	
}
