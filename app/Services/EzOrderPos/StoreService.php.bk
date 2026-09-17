<?php

namespace App\Services\QuickOrder;

use App\Facades\AppManager;
use App\Facades\PurchaseManager;
use App\Repositories\QuickOrderRepository;
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
   
	public function __construct(protected QuickOrderRepository $_repository)
	{
		$this->_statistics = [
			'brandId'		=> '', #export
			'brandCode'		=> '',
			'type'			=> '',
			'startDate'		=> '', #Y-m-d
            'endDate'   	=> '',
			'areaIds'		=> [],
			'storeName'		=> '',
			'data'			=> [],
			'exportToken'	=> '', #export
		];
	}
	
	/* ====================== 主流程 ====================== */
	/* ====================== 主流程 By Name ====================== */
	/* Search data
	 * @params: array
	 * @return: array
	 */
	public function analysis($params)
	{
		try
		{
			#無符合的posid可查
			if ($this->_prepareParams($params) === FALSE)
				return $this->_statistics;
			
			$this->_prepareData($params);
			dd($params->qoBaseData);
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
		$this->_statistics['shop']		= $params->shop;
		$this->_statistics['area']		= $params->area;
		
		#無值不cache
		if (! empty(Arr::flatten($this->_statistics['shop'])))
		{
			$this->_statistics['exportToken'] = bin2hex($params->cacheKey); #hex2bin
			Cache::put($params->cacheKey, $this->_statistics, now()->addMinutes(10));
		}
	}
	
	/* ====================== 主流程 End ====================== */
	
	/* PrepareParams
	 * @params: array
	 * @return: array
	 */
	private function _prepareParams($params)
	{
		try
		{
			#因不同來源要對齊,故先轉換成PosId, 且要先做判別
		
			#1. Get all shops with area permission
			$this->_getActiveStoreList($params);
			
			#2. Get posIds
			$this->_getPosIdsByParams($params);
			
			if ($params->type != 'all' && empty($params->posIds))
				return FALSE;
		
			#3. 取要統計的門店清單
			$this->_getOutputStores($params);
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception($e->getMessage());
		}
	}
	
	/* Get posIds
	 * @params: fluent
	 * @return: array
	 */
	private function _getActiveStoreList($params)
	{
		#以訂貨的為基準, 因八方點是用訂貨的store(取有權限的全部與查詢area無關)
		$storeList = PurchaseManager::getStoreList($params->brand, $params->userAreaIds, $params->stDate, $params->endDate);
		
		#須濾除廠區學區店, 因為沒有posid, 無法有銷售對應
		$brandId = $params->brand->value;
		$excepts = array_merge(config("web.quick_order.store.factoryStore.{$brandId}"), config("web.quick_order.store.except.{$brandId}"));
		
		$storeList = collect($storeList)->reject(function($item, $key) use($excepts){
			return in_array($item['storeKey'], $excepts) OR empty($item['posId']);
		})->all();
		/* ->mapWithKeys(function($item, $key) {
			return [$item['posId'] => $item];
		}) */
		
		$params->allStores = $storeList;
	}
	
	/* Get posIds
	 * @params: fluent
	 * @return: array
	 */
	private function _getPosIdsByParams($params)
	{
		#因八方點無area,且store來源不同, 故統一用posid來取值
		
		$posIds 	= [];
		$areaIds 	= $params->areaIds;
		$storeName 	= $params->storeName;
		$stores		= collect($params->allStores);
		
		if (! empty($areaIds))
		{
			$posIds = $stores->filter(function($item, $key) use($areaIds){
				return in_array($item['areaId'], $areaIds);
			})->pluck('posId')->all();
		}
		else if (! empty($storeName))
		{
			$posIds = $stores->filter(function($item, $key) use($storeName){
				return Str::contains($item['storeName'], $storeName);
			})->pluck('posId')->all();
		}
		
		$params->posIds = $posIds;
	}
	
	/* Get store list for output
	 * @params: fluent
	 * @return: array
	 */
	private function _getOutputStores($params)
	{
		#依條件過濾最終要輸出的門店
		
		$posIds 	= $params->posIds;
		$areaIds 	= $params->areaIds;
		$allStores	= collect($params->allStores);
		$storeList = [];
		
		if ($params->type == 'all')
			$storeList = $allStores;
		else if ($params->type == 'area')
		{
			$storeList = $allStores->filter(function($item, $key) use($areaIds){
				return in_array($item['areaId'], $areaIds);
			})->all();
		}
		else if ($params->type == 'storeName')
		{
			$storeList = $allStores->filter(function($item, $key) use($posIds){
				return in_array($item['posId'], $posIds);
			})->all();
		}
		
		$params->storeList = $storeList;
	}
	
	
	
	/* Get search data
	 * @params: array
	 * @return: array
	 */
	private function _prepareData($params)
	{
		try
		{
			#1. Get data from pos
			$posData = $this->_getDataFromPos($params);
			
			#2.build to base data
			$this->_buildPosBaseData($params, array_filter($posData)); 
			
			#3. Get data from quick order
			$qoData = $this->_getDataFromQuickOrder($params);
			
			#3.build to base data
			$this->_mergeQuickOrderToBaseData($params, array_filter($qoData)); 
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception($e->getMessage());
		}
	}
	
	
	
	
	/* Get pos data
	 * @params: fluent
	 * @return: array
	 */
	private function _getDataFromPos($params)
	{
		/* 0 => array:7 [
			"shopId" => "0795"
			"saleDate" => "2026-06-24"
			"customers" => "59"
			"amount" => "8537.0000"
			"totalSales" => "8538.0000"
			"totalExtra" => ".0000"
			"totalDischarge" => "-1.0000"
		] 
		*/
		try
		{
			$brand 			= $params->brand;
			$stDate			= (new Carbon($params->stDate))->format('Y-m-d 00:00:00');
			$endDate 		= (new Carbon($params->endDate))->addDay()->format('Y-m-d H:i:s');
			$posIds 		= $params->posIds;
			
			#帶入的是查詢的area
			$result = $this->_repository->getSaleFromPos($brand, $stDate, $endDate, $posIds);
			
			return $result;
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('讀取POS系統訂單資料失敗');
		}
	}
	
	/* POS基底資料(DB已計算Sum)
	 * @params: collection
	 * @return: array
	 */
	private function _buildPosBaseData($params, $posData)
	{
		#以訂貨門店為基礎, 補全門店資訊
		$storeList = $params->storeList;
		
		$posData = collect($posData)->mapWithKeys(function($item, $key){
			return [$item['shopId'] => $item];
		});
		
		$baseData = collect($storeList)->map(function($item, $key) use($posData){
			$data = data_get($posData, $item['posId']);
			
			$temp['storeKey'] 	= $item['storeKey'];
			
			#二取一因可能有空值
			#發票金額 = amount OR totalSales + totalDischarge
			#實銷金額 = totalSales + totalExtra + totalDischarge
			$amount 	= floatval(data_get($data, 'amount', 0));
			$totalSales = floatval(data_get($data, 'totalSales', 0) + data_get($data, 'totalDischarge', 0));
			$temp['amount'] 	= empty($amount) ? $totalSales : $amount;
			$temp['customers']	= data_get($data, 'customers', 0);
			
			$saleDate 	= data_get($data,'saleDate', NULL);
			$temp['saleDate']	= empty($saleDate) ? '' : (new Carbon($saleDate))->format('Y-m-d');
			$temp['saleWeek']	= empty($saleDate) ? '' : (new Carbon($saleDate))->isoFormat('\wWW');
			$temp['saleMonth']	= empty($saleDate) ? '' : (new Carbon($saleDate))->format('Y-m');
			
			return $temp; 
		})->values();
		
		$params->posBaseData = $baseData;
	}
	
	
	/* 取統計相關參數
	 * @params: enums
	 * @params: integer
	 * @return: array
	 */
	private function _getDataFromQuickOrder($params)
	{
		try
		{
			$brand 			= $params->brand;
			$stDate			= (new Carbon($params->stDate))->format('Y-m-d 00:00:00');
			$endDate 		= (new Carbon($params->endDate))->addDay()->format('Y-m-d H:i:s');
			$posIds 		= $params->posIds;
			
			#帶入的是查詢的area
			$result = $this->_repository->getSaleFromQuickOrder($brand, $stDate, $endDate, $posIds);
			
			return $result;
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('讀取八方點訂單資料失敗');
		}
	}
	
	/* Merge to POS基底資料
	 * @params: collection
	 * @return: array
	 */
	private function _mergeQuickOrderToBaseData($params, $qoData)
	{
		#以訂貨門店為基礎, 補全門店資訊
		$storeList = $params->storeList;
		
		$qoData = collect($qoData)->mapWithKeys(function($item, $key){
			return [$item['storeId'] => $item];
		});
		
		$baseData = collect($storeList)->map(function($item, $key) use($qoData){
			$data = data_get($qoData, $item['storeKey']);
			
			$temp['storeKey'] 	= $item['storeKey'];
			$temp['amount'] 	= floatval(data_get($data, 'amount', 0));
			$temp['customers']	= data_get($data, 'customers', 0);
			
			$saleDate = data_get($data,'saleDate', NULL);
			$temp['saleDate']	= empty($saleDate) ? '' : (new Carbon($saleDate))->format('Y-m-d');
			$temp['saleWeek']	= empty($saleDate) ? '' : (new Carbon($saleDate))->isoFormat('\wWW');
			$temp['saleMonth']	= empty($saleDate) ? '' : (new Carbon($saleDate))->format('Y-m');
			
			return $temp; 
		})->values();
		
		$params->qoBaseData = $baseData;
	}
	
	
	/* Get order data
	 * @params: enum
	 * @params: string
	 * @params: array
	 * @return: array
	 */
	private function _getOrderByMonth($args)
	{
		/* [
			"expectedDate" => "2026-06-01"
			"qty" => "40"
			"amount" => "2800.000000"
			"productName" => "招牌餡"
			"shortCode" => "0001"
			"memo" => "170823最小單位改10斤 1070603改回最小單位5斤"
		]
		*/
	
		try
		{
			$brandCode	= config("web.quick_order.store.code.{$args->brand->value}");
			$stDate		= (new Carbon($args->stDate))->format('Y-m-d 00:00:00');
			$endDate 	= (new Carbon($args->endDate))->format('Y-m-d H:i:s');
			
			#Get orders
			$orderData = $this->_repository->getOrders($brandCode, $stDate, $endDate);
			$storeList = $args->storeList;
			
			#補全門店Area參數
			$orderData = collect($orderData)->map(function($item, $key) use($storeList){
				
				$store = data_get($storeList, $item['storeId'], NULL);
				
				if(! is_null($store))
				{
					$item['areaId'] 	= $store['areaId'];
					$item['areaName'] 	= $store['areaName'];
					$item['orderDate']	= Carbon::parse($item['orderDate'])->format('Y-m');
					return $item;
				}
				
			})->toArray();
			
			$args->orderData = $orderData;
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('讀取八方點訂單資料失敗');
		}
	}
	
	/* 依月統計
	 * @params: array
	 * @return: array
	 */
	private function _outputByMonth($args)
	{
		/*
		"2026-01" => array:7 [▼
			"storeCount" => 1039
			"storeUsage" => 909
			"usageRate" => 87.49
			"customers" => 238107
			"amount" => 46845777.0
			"aov" => 196.74
			"area" => array:5 [▼
			  1 => array:7 [▼
				"areaName" => "大台北區"
				"storeCount" => 408
				"storeUsage" => 362
				"usageRate" => 88.73
				"customers" => 132029
				"amount" => 25188459.0
				"aov" => 190.78
			  ]
			  3 => array:7 [▶]
			  4 => array:7 [▶]
			  5 => array:7 [▶]
			  6 => array:7 [▶]
			]
		]
		*/
		$orderData = $args->orderData;
		
		if (empty($orderData))
			return [];
		
		$storeList = collect($args->storeList);
		$orderData = collect($args->orderData);
		
		#parsing
		$result = [];
		
		$result['storeCount'] 	= $storeList->count(); #有效門店統數
		$result['storeUsage'] 	= $orderData->count(); #使用門店數
		$result['usageRate'] 	= round($result['storeUsage'] / $result['storeCount'], 4) * 100; #使用率
		$result['customers']	= $orderData->pluck('customerCount')->sum();
		$result['amount']		= $orderData->pluck('amount')->sum();
		$result['aov']			= round($result['amount'] / $result['customers'], 2); #客單價
		
		$result['area'] = $storeList->groupBy('areaId')->map(function($items, $key) use($orderData) {
			$areaOrders = $orderData->where('areaId', $key);
			
			$temp['areaName']	= $items->pluck('areaName')->first();
			$temp['storeCount']	= $items->count();
			$temp['storeUsage']	= $areaOrders->count();
			$temp['usageRate'] 	= round($temp['storeUsage'] / $temp['storeCount'], 4) * 100; 
			$temp['customers']	= $areaOrders->pluck('customerCount')->sum();
			$temp['amount']		= $areaOrders->pluck('amount')->sum();
			$temp['aov']		= round($temp['amount'] / $temp['customers'], 2); #客單價
			
			return $temp;
		})->all();
		
		return $result;
	}
	
	/* 綜合計算全年結果
	 * @params: array
	 * @return: array
	 */
	private function _outputReport($data)
	{
		try
		{
			$result = [];
			$data = collect($data);
			$months = $data->count();
			
			$result['total']['storeCount'] = intval($data->pluck('storeCount')->sum() / $months);
			$result['total']['storeUsage'] = round($data->pluck('storeUsage')->sum() / $months, 2);
			$result['total']['usageRate'];
			$result['total']['customers'];
			$result['total']['amount'];
			$result['total']['aov'];
			dd($result);
			return $params;
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('解析報表資料發生錯誤');
		}
	}
	
	/* ====================== 主流程 End ====================== */
	
	/* Export data
	 * @params: array
	 * @return: array
	 */
	public function export($sourceData)
	{
		try
		{
			#Build export data for sheets
			$export = $this->_buildExportData($sourceData['purchaseData'], $sourceData['saleData']);
			
			$storeName = data_get($sourceData, 'storeInfo.storeName', '');
			#Write export to file
			$brandName = Brand::tryFrom($sourceData['brandId'])->label();
			$fileName = Str::replaceArray('?', [$brandName, $storeName, $sourceData['searchDate']], '?_?_訂貨及銷售_?.xlsx');
			$filePath = Storage::disk('export')->path($fileName);
			
			$writer = new Writer();
			$writer->openToFile($filePath);
			
			$index = 0;
			foreach($export as $sheetKey => $sheetData)
			{
				$sheet = ($index == 0) ? $writer->getCurrentSheet() : $writer->addNewSheetAndMakeItCurrent();
				$sheetName = ($sheetKey == 'purchase') ? '訂貨' : '銷售';
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
	private function _buildExportData($purchase, $sale)
	{
		#Header row
		$export['purchase'][] 	= $purchase['header'];
		$export['sale'][] 		= $sale['header'];
		
		foreach($purchase['data'] as $data)
		{
			$row = [];
			$row[] = $data['shortCode'];
			$row[] = $data['productName'];
			$row[] = $data['qty'];
			$row[] = $data['amount'];
			$row[] = $data['memo'];
			
			$export['purchase'][] = $row;
		}
		
		foreach($sale['data'] as $data)
		{
			$row = [];
			$row[] = $data['erpNo'];
			$row[] = $data['productName'];
			$row[] = $data['qty'];
			$row[] = $data['amount'];
			
			$export['sale'][] = $row;
		}
		
		return $export;
	}
}
		