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
class rangeService
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
			'store' 		=> [],
			'area' 			=> [],
			'exportName'	=> '',
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
			#原來的store service
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
		$this->_statistics['brandId']	= $params->brand->value;
		$this->_statistics['brandCode']	= $params->brand->code();
		$this->_statistics['type']		= $params->type;
		$this->_statistics['startDate'] = $params->stDate;
		$this->_statistics['endDate']	= $params->endDate;
		$this->_statistics['store']		= $params->store;
		$this->_statistics['area']		= $params->area;
		$this->_statistics['hasResult']	= FALSE;
		
		#無值不cache,因有補全門店,故hasResult應該都是TRUE
		if (! empty(Arr::flatten($this->_statistics['store'])))
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
			
			#3. Get data from DB
			$this->_getDataFromDB($params);
			
			#4.build to base data
			$this->_buildBaseData($params);
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception($e->getMessage());
		}
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
		
		$params->storeList = StoreManager::filterFactoryStore($brand, $storeList);
	}
	
	/* Get buy good data
	 * @params: fluent
	 * @return: array
	 */
	private function _getDataFromDB($params)
	{
		try
		{
			$brand 			= $params->brand;
			$stDate			= (new Carbon($params->stDate))->format('Y-m-d 00:00:00');
			$endDate 		= (new Carbon($params->endDate))->addDay()->format('Y-m-d H:i:s');
			$storeType 		= $params->storeType;
			$namePosIds 	= $params->namePosIds;
			$allowAreaIds 	= $params->allowAreaIds;
			
			$result = $this->_repository->getSale00Data($brand, $allowAreaIds, $stDate, $endDate, $storeType, $namePosIds);
			
			$params->saleData = array_filter($result);
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
		/*
		0 => array:8 [▼
		  "shopId" => "0035"
		  "storeName" => "0035中壢海華直營店"
		  "areaId" => 3
		  "areaName" => "桃竹苗區"
		  "shopType" => "1"
		  "shopTypeName" => "直營店"
		  "saleDate" => "2026-05-10"
		  "amount" => "18735.0000"
		]
		*/
		
		#即時營收取有效店家即可
		$saleData	= PosManager::filterDataByExceptStore($params->brand, $params->saleData);
		
		#因storeList已是有效門店, 改為由門店關聯資料
		$storeList 	= StoreManager::filterStoreByTypeName($params->storeList, $params->storeType);
		
		$saleData 	= collect($saleData)->map(function($item, $key){
			
			$temp['shopId'] 		= $item['shopId'];
			$temp['saleDate'] 		= $item['saleDate'];
			
			#實銷金額 = totalSales
			#實收金額 = totalSales + totalExtra
			#發票金額 = totalSales + totalDischarge
			#amount	= totalSales+ totalDischarge
			$amount 		= floatval($item['amount']);
			$totalSales 	= floatval($item['totalSales']) + floatval($item['totalDischarge']);
			$temp['amount'] = empty($totalSales) ? $amount : $totalSales;
			
			return $temp;
		})->groupBy('shopId')->toArray();
		
		$baseData = collect($storeList)->map(function($item, $key) use($saleData){
			
			$data = data_get($saleData, $item['posId'], []);
			
			#取需要的欄位
			$temp['storeKey'] 	= $item['storeKey'];
			$temp['shopId'] 	= $item['posId'];
			$temp['storeName'] 	= $item['storeName'];
			$temp['typeName'] 	= $item['typeName'];
			$temp['areaId'] 	= $item['areaId'];
			$temp['areaName']	= $item['areaName'];
			$temp['data']		= $data;
			
			return $temp; 
		})->values()->all();
		
		$params->baseData = $baseData;
	}
	
	/* 基底資料(DB已計算Sum)
	 * @params: collection
	 * @return: array
	 */
	/* private function _buildBaseData($params)
	{
		/*
		0 => array:8 [▼
		  "shopId" => "0035"
		  "storeName" => "0035中壢海華直營店"
		  "areaId" => 3
		  "areaName" => "桃竹苗區"
		  "shopType" => "1"
		  "shopTypeName" => "直營店"
		  "saleDate" => "2026-05-10"
		  "amount" => "18735.0000"
		]
		*#/
		
		#即時營收取有效店家即可
		$saleData	= PosManager::filterDataByExceptStore($params->brand, $params->saleData);
		
		$storeList 	= StoreManager::filterStoreByTypeName($params->storeList, $params->storeType);
		
		$storeList 	= collect($storeList)->mapWithKeys(function($item, $key){
			return [$item['posId'] => $item];
		});
		
		$baseData = collect($saleData)->map(function($item, $key) use($storeList){
			$store = data_get($storeList, $item['shopId'], NULL);
			
			if (empty($store))
				return '';
			
			$temp['storeKey'] 		= $store['storeKey'];
			$temp['shopId'] 		= $item['shopId'];
			$temp['storeName'] 		= $store['storeName'];
			$temp['shopTypeName']	= $item['typeName'];
			$temp['areaId'] 		= $store['areaId'];
			$temp['areaName']		= $store['areaName'];
			$temp['saleDate']		= Carbon::parse($item['saleDate'])->format('Y-m-d');
			
			#發票金額 = amount OR totalSales + totalExtra + totalDischarge
			#實銷金額 = totalSales, 應該只有totalSales?
			$amount 		= floatval($item['amount']);
			$totalSales 	= floatval($item['totalSales']) + floatval($item['totalExtra']) + floatval($item['totalDischarge']);
			$temp['amount'] = empty($amount) ? $totalSales : $amount;
			
			return $temp; 
		})->reject(function($item, $key){
			return empty($item);
		});
		
		#補全未有銷售的門店資料(closedown = 0)
		$saleShopIds = $baseData->pluck('shopId')->filter()->unique()->values()->toArray();
		#$filloutShops = PosManager::getFillOutStore($params->activeShopList, $saleShopIds);
		
		#重建
		$filloutShops = $storeList->reject(function($item, $key) use($saleShopIds){
			return in_array($item['posId'], $saleShopIds);
		})->map(function($item, $key) use($params){
			$temp['storeKey'] 		= $item['storeKey'];
			$temp['shopId'] 		= $item['posId'];
			$temp['storeName'] 		= $item['storeName'];
			$temp['shopTypeName']	= $item['typeName'];
			$temp['areaId'] 		= $item['areaId'];
			$temp['areaName']		= $item['areaName'];
			$temp['saleDate'] 		= $params->endDate;
			$temp['amount'] 		= 0;
			$temp['totalSales']		= 0;
			
			return $temp;
		});
		
		$params->baseData = $baseData->merge($filloutShops)->sortBy('areaId')->toArray();
	} */
	
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
			$this->_buildDayRange($params);
			
			#2.By區域
			$this->_parsingByArea($params);
			
			#3.By店別
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
	private function _buildDayRange($params)
	{
		$st 		= Carbon::create($params->stDate);
		$end 		= Carbon::create($params->endDate);
		$period 	= CarbonPeriod::create($st, $end);
		
		$dateList = [];

		foreach ($period as $date) 
		{
			$dateString = $date->format('Y-m-d');
			$dateList[$dateString] = $dateString;
		}
		
		$params->dayRange = $dateList;
	}
	
	
	/* 區域營收By Day
	 * @params: array
	 * @return: array
	 */
	private function _parsingByArea($params)
	{
		/*
		"areaId" => [
			"areaName" =>"大台北區"
			"shopCount" => 11
			"dayAmount" => [
				"2026-03-18" => 101
				"2026-03-19" => 22208
			]
			"大高雄區" => array:5 []
			"宜蘭區" => array:5 []
			"中彰投區" => array:5 []
			"雲嘉南區" => array:5 []
			"桃竹苗區" => array:5 []
		]
		*/
		
		$params->set('area.header', []);
		$params->set('area.data', []);
		
		$baseData = $params->baseData;
		
		#會有無設定區域權限的狀況, 須判別
		if (empty($baseData))
			return [];
		
		$header = ['areaName' => '區域', 'storeCount' => '門店數', 'dayAmount' => $params->dayRange];
		$params->set('area.header', $header);
		
		#這裏也是By day
		$result = collect($baseData)->groupBy('areaId')->map(function($items, $key) {
			
			$temp['areaName']		= $items->pluck('areaName')->first();
			$temp['storeCount']		= $items->pluck('storeKey')->unique()->count(); #店家數
			
			#整理Amount成Daily形式
			$data = $items->pluck('data')->collapse();
			
			$temp['dayAmount'] = $data->groupBy('saleDate')->mapWithKeys(function($items, $date) {
				
				#因amount會有0的狀況
				$amount	= $items->pluck('amount')->sum();
				return [$date => round($amount, 2)];
			})->filter(function($item, $key){
				return $key > 0;
			})->toArray();
			
			return $temp;
		})->sortKeys()->toArray();
		
		#全區總計
		$result['total']['areaName'] 	= '總計'; 
		$result['total']['storeCount'] 	= collect($baseData)->pluck('shopId')->unique()->count(); 
		$result['total']['dayAmount'] 	= collect($baseData)->pluck('data')->collapse()->groupBy('saleDate')->mapWithKeys(function($items, $date) {
			
			$amount	= $items->pluck('amount')->sum();
			return [$date => round($amount)];
		})->filter(function($item, $key){
			return $key > 0;
		})->toArray();
		
		$params->set('area.data', $result);
	}
	
	/* 店別每日營收
	 * @params: array
	 * @return: array
	 */
	private function _parsingByStore($params)
	{
		/* Output: 20260510改併成一個array,也方便export
		[
		330002 => [
			"storeName" => "御廚豐原向陽店"
			"areaName" => "中彰投區"
			"dayAmount" =>  [
				"2025-09-15" => 666.0
				"2025-09-14" => 777.0
			]
		]
		*/
		
		$params->set('store.header', []);
		$params->set('store.data', []);
		
		$baseData = $params->baseData;
		
		#會有無設定區域權限的狀況, 須判別
		if (empty($baseData))
			return [];
		
		$header = ['areaName' => '區域', 'shopId' => 'Pos店號', 'storeKey' => '門店代號', 'storeName' => '門店名稱', 'storeTypeName' => '類型',
					'dayAmount' => $params->dayRange
				];
		$params->set('store.header', $header);
		
		#Sum已在DB計算, 這裏只要format output
		$result = collect($baseData)->map(function($item, $key) {
			
			$temp['storeKey'] 		= $item['storeKey'];
			$temp['shopId'] 		= $item['shopId'];
			$temp['storeName'] 		= $item['storeName'];
			$temp['storeTypeName'] 	= $item['typeName'];
			$temp['areaId'] 		= $item['areaId'];
			$temp['areaName'] 		= $item['areaName'];
			
			$temp['dayAmount'] = collect($item['data'])->groupBy('saleDate')->map(function($items, $date) {
				$amount	= floatval($items->pluck('amount')->sum());
				return round($amount, 2);
			})->filter(function($item, $key){
				return $key > 0;
			})->toArray();
			
			return $temp; 
		})->values()->toArray();
		
		#By日總計
		$result['total']['storeKey'] 		= ''; 
		$result['total']['shopId'] 			= ''; 
		$result['total']['storeName'] 		= '總計'; 
		$result['total']['storeTypeName']	= ''; 
		$result['total']['areaName'] 		= ''; 
		
		$totalData = collect($baseData)->pluck('data')->collapse();
		
		$result['total']['dayAmount'] = collect($totalData)->groupBy('saleDate')->map(function($items, $date) {
			$amount	= floatval($items->pluck('amount')->sum());
			return round($amount, 2);
		})->toArray();
		
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
			$export['區域彙總'] = $this->_buildExportArea($sourceData['area']);
            $export['店別明細'] = $this->_buildExportShop($sourceData['store']);
			
			#Write export to file
			$brandName = Brand::tryFrom($sourceData['brandId'])->label();
			$fileName = Str::replaceArray('?', [$brandName, $sourceData['startDate'], $sourceData['endDate']], '?_門店營收_?_?.xlsx');
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
	private function _buildExportArea($srcData)
	{
		$export = [];
		$export[] = Arr::flatten($srcData['header']);
		
		#Area data
		foreach($srcData['data'] as $key => $area)
		{
			if (empty($area))
				continue;
			
			$row = [];
			$row[] = $area['areaName'];
			$row[] = $area['storeCount'];
				
			#要按Header的順序
			foreach($srcData['header']['dayAmount'] as $colKey)
			{
				$amount = data_get($area, "dayAmount.{$colKey}", 0);
				$row[] = Number::currency($amount, precision: 0);
			}
			
			$export[] = $row;
		}
		
		return $export;
	}
	
	/* Build data for export
	 * @params: array
	 * @return: array
	 */
	private function _buildExportShop($srcData)
	{
		$export[] = Arr::flatten($srcData['header']);
		
		foreach($srcData['data'] as $shopId => $shop)
		{
			$row = [];
			$row[] = $shop['areaName'];
			$row[] = $shop['shopId'];
			$row[] = $shop['storeKey'];
			$row[] = $shop['storeName'];
			$row[] = $shop['storeTypeName'];
			
			foreach($srcData['header']['dayAmount'] as $colKey)
			{
				$amount = data_get($shop, "dayAmount.{$colKey}", 0);
				$row[] = Number::currency($amount, precision: 0);
			}
			
			$export[]= $row;
		}
		
		return $export;
	}
}
