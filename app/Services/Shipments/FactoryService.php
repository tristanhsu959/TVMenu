<?php

namespace App\Services\Shipments;

use App\Facades\AppManager;
use App\Facades\PurchaseManager;
use App\Facades\StoreManager;
use App\Facades\LocalLegacyManager;
use App\Repositories\ShipmentsRepository;
use App\Libraries\ResponseLib;
use App\Enums\OpCenter;
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
use OpenSpout\Common\Entity\Style\Style; 
use OpenSpout\Common\Entity\Style\CellAlignment;

#partial Service
class FactoryService
{
	private $_statistics = [];
	
	public function __construct(protected ShipmentsRepository $_repository)
	{
		$this->_statistics = [
			'type'		=> '',
			'by'		=> '',
			'calc'		=> '',
			'brandId'		=> '', #export
			'brandCode'		=> '', 
			'startDate'		=> '', #Y-m-d
            'endDate'   	=> '',
			'productIds'	=> [],
			'dateList'		=> [],
			'productList'	=> [],
			'factoryList'	=> [],
			'data'			=> [],
			'exportName'	=> '', #export
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
	 * @params: array
	 * @return: array
	 */
	private function _generateStatistics($params)
	{
		$this->_statistics['type']			= $params->type;
		$this->_statistics['by']			= $params->by; 
		$this->_statistics['calc']			= $params->calc; 
		$this->_statistics['brandId']		= $params->brand->value; 
		$this->_statistics['brandCode']		= $params->brand->code(); 
		$this->_statistics['startDate'] 	= $params->stDate; 
		$this->_statistics['endDate'] 		= $params->endDate;
		$this->_statistics['dateList'] 		= $params->dateList;
		$this->_statistics['productList'] 	= $params->productList;
		$this->_statistics['factoryList'] 	= $params->factoryList;
		$this->_statistics['data'] 			= $params->data;
		$this->_statistics['hasResult'] 	= FALSE;
		
		#無值不cache
		if (! empty($params->data))
		{
			$this->_statistics['hasResult'] 	= TRUE;
			$this->_statistics['exportToken'] 	= bin2hex($params->cacheKey); #hex2bin
			$name = [];
			$name[] = '工廠';
			$name[] = ($params->calc == 'day') ? 'BY日' : 'BY月';
			
			$this->_statistics['exportName'] = Arr::join($name, '_');
			Cache::put($params->cacheKey, $this->_statistics, now()->addMinutes(10));
		}
	}
	
	/* ====================== 主流程 End ====================== */
	
	/* 取統計相關參數
	 * @params: array
	 * @return: array
	 */
	private function _prepareData($params)
	{
		try
		{
			$this->_getStoreList($params);
			
			$this->_getProductId($params);
			
			$this->_getDataFromDB($params);
			
			#未來若建在新系統, 直接mark即可
			$this->_getExtraDataFromDB($params); #追加目前在舊系統,要另外處理
			
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
	
	/* 以short code取得product id
	 * @params: array
	 * @return: array
	 */
	private function _getProductId($params)
	{
		try
		{
			#取資料都不分營運中心,不然可能會取不到
			$brand 		= $params->brand;
			$opCenterIds= $params->allowOpCenterIds;
			$areaIds 	= $params->allowAreaIds;
			
			if ($params->where == 'keyword')
				$params->productIds = PurchaseManager::getProductIdByName($brand, $opCenterIds, $params->keyword);
			else if ($params->where == 'category')
				$params->productIds = PurchaseManager::getProductIdByShortCode($brand, $opCenterIds, $params->shortCodes);
			else
				$params->productIds = [];
			
			if (empty($params->productIds))
				throw new Exception('查無此產品');
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception($e->getMessage());
		}
	}
	
	/* Get order data
	 * @params: array
	 * @return: array
	 */
	private function _getDataFromDB($params)
	{
		/*0 => array:9 [
			"expectedDate" => "2026-03-25"
			"area" => "中彰投-八方"
			"storeId" => "156"
			"factoryNo" => "TW_KH"
			"factoryName" => "高雄工廠"
			"qty" => "2"
			"amount" => "500.000000"
			"productName" => "紅燒帶骨牛小排調理包"
			"erpNo" => "PR00313063"
		  ]
		*/
	
		try
		{
			$brand 				= $params->brand;
			$stDate				= (new Carbon($params->stDate))->format('Y-m-d 00:00:00');
			$endDate 			= (new Carbon($params->endDate))->addDay()->format('Y-m-d H:i:s');
			$productIds			= $params->productIds;
			$allowOpCenterIds 	= $params->allowOpCenterIds;
			$allowAreaIds 		= $params->allowAreaIds;
			
			#已包含蘿蔔訂單
			$orderData = $this->_repository->getOrderDataByProductId($brand, $allowOpCenterIds, $allowAreaIds, $stDate, $endDate, $productIds);
			
			$params->orderData = $orderData;
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('讀取訂貨系統訂單資料失敗');
		}
	}
	
	/* Get extra order data from old system
	 * @params: array 
	 * @return: array
	 */
	private function _getExtraDataFromDB($params)
	{
		try
		{
			$brand 			= $params->brand;
			$stDate			= (new Carbon($params->stDate))->format('Y-m-d 00:00:00');
			$endDate 		= (new Carbon($params->endDate))->addDay()->format('Y-m-d H:i:s');
			$productCodes	= $params->shortCodes;
			$allowOpCenterIds 	= $params->allowOpCenterIds;
			$allowAreaIds 		= $params->allowAreaIds;
			
			$extraData = LocalLegacyManager::getExtraDataByProduct($brand, $allowOpCenterIds, $stDate, $endDate, $productCodes);
			
			#因無areaId, 故只能從門店過濾
			$validStoreKeys = collect($params->storeList)->pluck('storeKey')->values()->all();
			
			$extraData = collect($extraData)->filter(function($item, $key) use($validStoreKeys) {
				$storeKey = Str::take($item['storeNo'], 7);
				return in_array($storeKey, $validStoreKeys);
			})->toArray();
			
			$params->extraData = $extraData;
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('讀取訂貨系統訂單資料失敗');
		}
	}
	
	/* 基底資料
	 * @params: array
	 * @params: array
	 * @params: array
	 * @return: array
	 */
	private function _buildBaseData($params)
	{
		#整合追加資料
		$baseData = collect($params->orderData)->merge($params->extraData);
		
		$authStoreKeys = collect($params->storeList)->pluck('storeKey')->unique()->all();
		
		#處理包裝轉換
		#因工廠沒門店, 要先濾除
		$baseData = collect($baseData)->map(function($item, $key){
			
			$item['storeKey'] = StoreManager::buildStoreKey($item['storeNo']);
			$item['qty'] = round(intval($item['qty']) * PurchaseManager::getPackagingScale($item['shortCode']), 2);
			
			return $item;
		})->filter(function($item, $key) use($authStoreKeys){
			
			return in_array($item['storeKey'], $authStoreKeys);
		})->toArray();
			
		$params->baseData = $baseData;
	}
	
	/* ========================== 統計 ========================== */
	/* ========================================================== */
	/* 處理統計資料輸出
	 * @params: array
	 * @return: array
	 */
	private function _outputReport($params)
	{
		try
		{
			#1.計算查詢範圍總天數 (use Date not DateTime)
			$this->_buildDateHeader($params);
			
			#2.Build productList
			$this->_getProductList($params);
		
			#3.Get factory list
			$this->_getFactoryList($params);

			#4. analysis by 門店
			$this->_parsingByFactory($params);
			
			return $params;
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('解析報表資料發生錯誤');
		}
	}
	
	/* 計算日期天數
	 * @params: array 
	 * @return: array
	 */
	private function _buildDateHeader($params)
	{
		$modeCalc 	= $params->calc;
		$header 	= [];
		
		if ($modeCalc == 'day')
		{
			#By day
			$st		= Carbon::create($params->stDate);
			$end 	= Carbon::create($params->endDate);
			$period = CarbonPeriod::create($st, $end);
			
			foreach ($period as $date) 
			{
				$header[] = $date->format('Y-m-d');
			}
		}
		else
		{
			#By month
			$st		= Carbon::parse($params->stDate)->startOfMonth();
			$end	= Carbon::parse($params->endDate)->startOfMonth();
			$period = CarbonPeriod::create($st, '1 month', $end);
			
			foreach ($period as $date) 
			{
				$header[] = $date->format('Y-m');
			}
		}
		
		$params->dateList = $header;
	}
	
	/* Get order data
	 * @params: array
	 * @return: array
	 */
	private function _getProductList($params)
	{
		$baseData = $params->baseData;
		
		$productList = collect($baseData)->groupBy('shortCode')->map(function($items, $key){
			#取新的為主, 新系統才有erpNo
			$item = $items->where('erpNo', '!=', '')->first();
			
			$temp['productName']= $item['productName'];
			$temp['memo']		= trim($item['memo']);
			
			return $temp;
		})->sortKeys()->toArray();
		
		$params->productList = $productList;
	}
	
	/* Get order data
	 * @params: array
	 * @return: array
	 */
	private function _getFactoryList($params)
	{
		try
		{
			#輸出用,故取全部工廠
			$opCenter	= OpCenter::toValueArray();
			$factory 	= PurchaseManager::getFactoryList($params->brand, $opCenter);
			
			$params->factoryList = $factory;
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('讀取工廠資料失敗');
		}
	}
	
	/* 依工廠
	 * @params: array
	 * @return: array
	 */
	private function _parsingByFactory($params)
	{
		/*
		"PR00313063" => array:2 [
			"TW_KH" => array:2 [
				"2026-03-25" => array:1 [
					"qty" => 116
				]
				"2026-03-26" => array:1 []
			]
			"TW_TP" => array:2 []
		]
		*/
		
		$orderData = $params->baseData;
		
		if (empty($orderData))
		{
			$params->data = [];
			return;
		}
		
		$modeCalc = $params->calc;
		
		$result = collect($orderData)->groupBy('shortCode')->map(function($items, $key) use($modeCalc) {
			$temp = $items->groupBy('factoryNo')->map(function($items, $key) use($modeCalc) {
				
				if ($modeCalc == 'day')
				{
					$day = $items->groupBy('expectedDate')->map(function($items, $key) {
						$temp['qty'] = round($items->pluck('qty')->sum(), 2);
						return $temp;
					})->toArray();
					
					return $day;	
				}
				
				if ($modeCalc == 'month')
				{
					$month = $items->groupBy(function ($item) {
						return substr($item['expectedDate'], 0, 7); 
					})->map(function ($group) {
						$temp['qty'] = round($group->pluck('qty')->sum(), 2);
						return $temp;
					})->toArray();
					
					return $month;	
				}
			});
			
			return $temp;
		})->sortKeys()->toArray();
		
		$params->data = $result;
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
			$export = $this->_buildExportData($sourceData);
			
			#Write export to file
			$brandName = Brand::tryFrom($sourceData['brandId'])->label();
			$fileName = Str::replaceArray('?', [$brandName, $sourceData['exportName'], $sourceData['startDate'], $sourceData['endDate']], '?_出貨總量_?_?_?.xlsx');
			$filePath = Storage::disk('export')->path($fileName);
			
			$writer = new Writer();
			$writer->openToFile($filePath);
			
			$centerStyle = (new Style())->setCellAlignment(CellAlignment::CENTER);
			
			$index = 0;
			foreach($export as $sheetName => $sheetData)
			{
				$sheet = ($index == 0) ? $writer->getCurrentSheet() : $writer->addNewSheetAndMakeItCurrent();
				$sheet->setName($sheetName);
				
				foreach($sheetData as $data)
				{
					$row =  Row::fromValues($data, $centerStyle);
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
	private function _buildExportData($sourceData)
	{
		$export = [];
		$outputHeader = array_merge(['出貨工廠'], $sourceData['dateList']);
		
		#每個product要一個sheet
		foreach($sourceData['productList'] as $shortCode => $item)
		{
			$factoryData = data_get($sourceData['data'], $shortCode, []);
			#不能用productName,會重複蓋掉
			$productName = "{$shortCode}_{$item['productName']}";
			
			if (empty($factoryData))
				continue;
			
			$export[$productName] = [];
			$export[$productName][] = $outputHeader;
			
			#使用header來控制顯示順序,先TP後KH
			foreach($sourceData['factoryList'] as $factoryNo => $factoryName)
			{
				$row = [];
				$row[] = $factoryName;
				
				#要按Header的順序
				foreach($sourceData['dateList'] as $date)
				{
					$row[] = data_get($factoryData, "{$factoryNo}.{$date}.qty", 0);
				}
				
				$export[$productName][] = $row;
			}
		}
		
		return $export;
	}
}
