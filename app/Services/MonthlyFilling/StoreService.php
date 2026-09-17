<?php

namespace App\Services\MonthlyFilling;

use App\Facades\AppManager;
use App\Facades\PurchaseManager;
use App\Facades\StoreManager;
use App\Facades\LocalLegacyManager;
use App\Repositories\MonthlyFillingRepository;
use App\Libraries\ResponseLib;
use App\Libraries\Purchase\AreaLib;
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
class StoreService
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
			'sheets'		=> [],
			'header'		=> [],
			'data'			=> [],
			'exportName'	=> '', #export
			'exportToken'	=> '', #export
		];
	}
	
	/* ====================== 主流程 By Name ====================== */
	/* Search data(因月初報表較固定模式,寫法不同)
	 * @params: array
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
		$this->_statistics['sheets']		= $params->sheets;
		$this->_statistics['header']		= $params->header;
		$this->_statistics['data']			= $params->data;
		$this->_statistics['hasResult'] 	= FALSE;
		
		#無值不cache
		if (! empty(Arr::flatten($this->_statistics['data'])))
		{
			$this->_statistics['hasResult'] 	= TRUE;
			$this->_statistics['exportName']	= '餡量BY店';
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
			
			#2.Get store
			$this->_getStoreList($params);
			
			#3.Get Purchase data
			$orderData = $this->_getDataFromDB($params);
			
			#4.Get extra data
			$extraData = $this->_getExtraDataFromDB($params);
			
			#5. Build base data
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
			$brand	 			= $params->brand;
			$codes				= array_map('strval', array_keys(config('web.purchase.monthly_filling.totalCount.code')));
			$allowOpCenterIds 	= $params->allowOpCenterIds;
			
			$ids = PurchaseManager::getProductIdByShortCode($brand, $allowOpCenterIds, $codes);
			
			if (empty($ids))
				throw new Exception('查無參照的產品');
			
			$params->productGroup	= config('web.purchase.monthly_filling.totalCount.group');
			$params->productCodes 	= $codes; #舊系統DB需用到
			$params->productIds 	= $ids;
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
	
	/* ====================== 主流程 End ====================== */
	
	/* Get order data
	 * @params: array
	 * @return: array
	 */
	private function _getDataFromDB($params)
	{
		/* [
			"expectedDate" => "2026-04"
			"qty" => "5"
			"storeId" => "152"
			"storeNo" => "KH1100100"
			"shortCode" => "0001"
		]
		*/
	
		try
		{
			$brand 				= $params->brand;
			$stDate				= (new Carbon($params->stDate))->format('Y-m-d 00:00:00');
			$endDate 			= (new Carbon($params->endDate))->format('Y-m-d 23:59:59');
			$productIds 		= $params->productIds;
			$allowOpCenterIds 	= $params->allowOpCenterIds;
			$allowAreaIds 		= $params->allowAreaIds;
			
			#取資料先不分OpCenter
			$orderData = $this->_repository->getOrderDataByStore($brand, $allowOpCenterIds, $allowAreaIds, $stDate, $endDate, $productIds);
			
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
				$storeKey = Str::take($item['storeNo'], 7); #因舊系統是7碼＋(1|2)
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
		
		$authStoreKeys = collect($params->storeList)->pluck('storeKey')->unique()->all();
		
		#處理包裝轉換
		#因追加在舊系統,故要改成storeKey做為主要關聯
		$baseData = collect($baseData)->map(function($item, $key){
			$temp['expectedDate']	= Carbon::parse($item['expectedDate'])->format('Y-m');
			$temp['storeNo'] 		= $item['storeNo'];
			$temp['storeKey'] 		= StoreManager::buildStoreKey($item['storeNo']);
			$temp['shortCode'] 		= $item['shortCode'];
			$temp['qty'] 			= round(intval($item['qty']) * PurchaseManager::getPackagingScale($item['shortCode']), 2);
			
			return $temp;
		})->filter(function($item, $key) use($authStoreKeys){
			
			return in_array($item['storeKey'], $authStoreKeys);
		})->toArray();
		
		$params->baseData = $baseData;
	}
	
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
			
			#2.Build header data
			$this->_buildHeader($params);
			
			#3.By門店
			#依產品產生一個sheet
			foreach($params->productGroup as $groupKey => $value)
			{
				$groupCodes = data_get($value, 'code', []);
				$parsingData = $this->_parsingByStore($params->baseData, $groupCodes); 
				
				$params->set("data.{$groupKey}", $this->_generateOutput($params, $parsingData));
			}
			
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
	
	/* Header
	 * @params: 
	 * @return: array
	 */
	private function _buildHeader($params)
	{
		$monthList 		= $params->monthList;
		$productGroup 	= $params->productGroup;
		
		$params->sheets = collect($productGroup)->mapWithKeys(function($item, $key){
			return [$key => $item['name']];
		})->toArray();
		
		$params->header	= array_merge(['POS ID', '區域', '門店代號', '門店名稱'], $monthList);
	}
	
	
	
	/* 依門店
	 * @params: array
	 * @return: array
	 */
	private function _parsingByStore($orderData, $groupCodes)
	{
		/*
		"g1" => array:1063 [
			"1911" => array:2 [
				"2026-03" => 1590
				]
				"2026-02" => array:1 [...]
			]
		]
		*/
		
		if (empty($orderData))
			return [];
		
		#先依定義的餡分群
		$result = collect($orderData)->filter(function($item, $key) use($groupCodes){
			return in_array($item['shortCode'], $groupCodes);
		
		})->groupBy('storeKey')->map(function($items, $key) {
			
			return $items->groupBy('expectedDate')->map(function($items, $key) {
				return $items->pluck('qty')->sum();
			})->toArray();
		
		})->toArray();
		
		return $result;
	}
	
	/* 改成產出row data
	 * @params: array
	 * @return: array
	 */
	private function _generateOutput($params, $data)
	{
		if (empty($data))
			return [];
		
		$storeList = $params->storeList;
		$monthList = $params->monthList;
		
		$rowData = [];
		
		foreach($storeList as $key => $store)
		{
			#$storeId = $store['storeId'];
			$storeData = data_get($data, $store['storeKey']);
			
			/* if (empty($storeData))
				continue; */
			
			$row = [];
			$row[] = data_get($store, 'posId');
			$row[] = data_get($store, 'areaName');
			$row[] = data_get($store, 'storeKey');
			$row[] = data_get($store, 'storeName');
			
			foreach($monthList as $month)
			{
				$row[] = data_get($storeData, $month, 0);
			}			
				
			$rowData[] = $row;
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
			$export = $this->_buildExportData($sourceData['sheets'], $sourceData['header'], $sourceData['data']);
			
			#Write export to file
			$brandName = Brand::tryFrom($sourceData['brandId'])->label();
			$fileName = Str::replaceArray('?', [$brandName, $sourceData['exportName'], $sourceData['startDate'], $sourceData['endDate']], '?_月初報表_?_?_?.xlsx');
			$filePath = Storage::disk('export')->path($fileName);
			
			$writer = new Writer();
			$writer->openToFile($filePath);
			
			$index = 0;
			foreach($export as $sheetName => $sheetData)
			{
				$sheet = ($index == 0) ? $writer->getCurrentSheet() : $writer->addNewSheetAndMakeItCurrent();
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
	private function _buildExportData($sheets, $header, $data)
	{
		/*
		"sheet" => array:4 [
			"g1" => "餡"
			"g2" => "粗細麵"
			"g3" => "菜肉餡"
			"g4" => "餛飩餡"
		]
		"tableHeader" => array:5 [
			0 => "POS ID"
			1 => "區域"
			2 => "門店代號"
			3 => "門店名稱"
			4 => "2026-03"
		]
		*/
		$export = [];
		
		#每個product要一個sheet
		foreach($sheets as $key => $sheetName)
		{
			$export[$sheetName] = [];
			$export[$sheetName][] = $header;
			
			$storeData = data_get($data, $key, []);
			
			if (empty($storeData))
				continue;
			
			foreach($storeData as $rowData)
			{
				$export[$sheetName][] = $rowData;
			}
		}
		
		return $export;
	}
}