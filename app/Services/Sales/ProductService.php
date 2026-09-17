<?php

namespace App\Services\Sales;

use App\Facades\AppManager;
use App\Facades\PosManager;
use App\Facades\StoreManager;
use App\Repositories\SalesRepository;
use App\Services\Sales\StoreService;
use App\Services\Sales\AreaService;
use App\Libraries\ResponseLib;
use App\Libraries\HelperLib;
use App\Enums\Brand;
use App\Enums\Functions;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Fluent;
use Illuminate\Support\Number;
use Illuminate\Support\Carbon;
use Carbon\CarbonPeriod;
use Exception;
use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style; 
use OpenSpout\Common\Entity\Style\CellAlignment;

#Partial service
class ProductService
{
	private $_statistics	= [];
	
	public function __construct(protected SalesRepository $_repository)
	{
	}
	
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
		$statistics['brandId']		= $params->brand->value;
		$statistics['brandCode']	= $params->brand->code();
		$statistics['startDate'] 	= $params->stDate;
		$statistics['endDate']		= $params->endDate;
		$statistics['store']		= $params->store;
		$statistics['area']			= $params->area;
		$statistics['detail']		= $params->detail;
		$statistics['productList']	= $params->productList;
		$statistics['exportToken']	= '';
		$statistics['hasResult']	= FALSE;
		
		#無值不cache
		if (! empty($statistics['store']['data']))
		{
			$statistics['hasResult']	= TRUE;
			$statistics['exportToken'] 	= bin2hex($params->cacheKey); #hex2bin
			Cache::put($params->cacheKey, $statistics, now()->addMinutes(10));
		}
		
		return $statistics;
	}
	
	/* ====================== Prepare data ====================== */
	/* Get search data
	 * @params: array
	 * @return: array
	 */
	private function _prepareData($params)
	{
		try
		{
			#1. Get product id list for sql
			$this->_getProductParams($params);
			
			#2. Get all shops with area permission
			$this->_buildProductMap($params);
			
			#3. Get all shops with area permission
			$this->_getStoreList($params);
			
			#4. Get shop id by Name
			$this->_getStoreIdByName($params);
			
			#5.get data
			$this->_getDataFromDB($params);
			
			#6.build to base data
			$this->_buildBaseData($params);
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception($e->getMessage());
		}
	}
	
	
	
	/* 取ErpNo
	 * @params: eunums
	 * @return: array
	 */
	private function _getProductParams($params)
	{
		try
		{
			#Dashboard product id
			$productList = $this->_repository->getProductByIds($params->productIds);
			
			#分開primary & secondary
			$primaryIds = collect($productList)->filter(function($item, $key){
				return $item['isPrimary'];
			})->pluck('erpNo')->toArray();
			
			$secondaryIds = collect($productList)->filter(function($item, $key){
				return ! $item['isPrimary'];
			})->pluck('erpNo')->toArray();
			
			#建立product list為key-value by erpNo, 做為取回資料mapping用
			$productList = collect($productList)->groupBy('erpNo')->map(function($item, $key) {
				$temp['productId'] 	= $item->pluck('productId')->first();
				$temp['productName']= $item->pluck('productName')->first();
				
				return $temp;
			})->toArray();
			
			$params->productList	= $productList; #erpNo=>productId list
			$params->primaryIds		= $primaryIds;
			$params->secondaryIds 	= $secondaryIds;
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('解析銷售參數發生錯誤');
		}
	}
	
	/* Product list header
	 * @params: collection
	 * @return: array
	 */
	private function _buildProductMap($params)
	{
		/*
		[ productId => productName
			2 => "橙汁排骨"
			3 => "蕃茄牛三寶"
			4 => "老皮嫩肉"
			5 => "主廚秘製滷肉飯"
			7 => "牛小排飯"
		]
		*/
		
		$productList = $params->productList;
		
		#是以DB product table有設定的產品為基礎
		$header =  collect($productList)->mapWithKeys(function ($item, $key) {
			return [$item['productId'] => $item['productName']];
		})->toArray();
		
		$params->productMap = $header;
	}
	
	/* 門店資料
	 * @params: collection
	 * @return: array
	 */
	private function _getStoreList($params)
	{
		$brand 				= $params->brand;
		$allowOpCenterIds 	= $params->allowOpCenterIds;
		$allowAreaIds 		= $params->allowAreaIds;
		$stDate				= $params->stDate;
		$endDate			= $params->endDate;
		
		#新品先過濾廠區學區店
		$storeList = StoreManager::getStoreList($brand, $allowOpCenterIds, $allowAreaIds, $stDate, $endDate);
		$storeList = PosManager::filterSpecialStore($brand, $storeList);
		
		$params->storeList = StoreManager::filterFactoryStore($brand, $storeList);
	}
	
	/* 取查詢的門店資料
	 * @params: collection
	 * @return: array
	 */
	private function _getStoreIdByName($params)
	{
		$storeName = $params->storeName;
		
		if (empty($storeName))
		{
			$params->storeIds = [];
			return TRUE;
		}
		
		$storeList = collect($params->storeList)->filter(function($item, $key) use($storeName){
			return Str::contains($item['storeName'], $storeName);
		});
		
		#有查店名,要更新store list
		$params->storeList 	= $storeList->toArray();
		$params->storeIds	= $storeList->pluck('posId')->values()->all(); 
		
		#沒查到門店
		if (empty($params->storeIds))
			throw new Exception('查無符合門店資料');
	}
	
	/* Get buy good data
	 * @params: fluent
	 * @return: array
	 */
	private function _getDataFromDB($params)
	{
		try
		{
			/* Return format */
			/*
			array:9 [
				"shopId" => "103002"
				"productId" => "UC06000002"
				"price_sum" => 111 => price * qty + discount
				"qty_sum" => 99
				"storeName" => "御廚重慶北直營店"
				"gid" => "A01"
				"productName" => "炸雞腿飯"
			]
			*/
			
			$brand 			= $params->brand;
			$stDate			= (new Carbon($params->stDate))->format('Y-m-d 00:00:00');
			$endDate 		= (new Carbon($params->endDate))->addDay()->format('Y-m-d H:i:s');
			$primaryIds 	= $params->primaryIds;
			$secondaryIds 	= $params->secondaryIds;
			$allowAreaIds 	= $params->allowAreaIds;
			$filterStoreIds	= $params->storeIds;
			
			$result = $this->_repository->getSaleData($brand, $allowAreaIds, $stDate, $endDate, $primaryIds, $secondaryIds, $filterStoreIds);
			
			$params->saleData = $result;
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('讀取POS系統訂單資料失敗');
		}
	}
	
	
	/* Rebuild data format
	 * @params: Fluent
	 * @params: array
	 * @return: array
	 */
	private function _buildBaseData($params)
	{
		/* 重整資料格式/命名/區域
		array:11 [
			"shopId" => "100001"
			"storeName" => "御廚中正南昌店"
			"erpNo" => "UC00000042"
			"price_sum" => "360.0"
			"qty_sum" => "3"
			"areaId" => 1
			"areaName" => "大台北區"
			"productId" => 2
			"productName" => "橙汁排骨"
		]
		*/
		$saleData = array_filter($params->saleData);
		
		if (empty($saleData))
		{
			$params->baseData = [];
			return;
		}
		
		#改用門店對應資料,因門店已是active,不用再處理補全門店
		$saleData = PosManager::filterDataByExceptStore($params->brand, $params->saleData);
		
		#用來convert erpNo to productId
		$productList = $params->productList; 
		
		#先處理Data再mapping
		$saleData = collect($saleData)->groupBy('shopId')->map(function($items, $key) use($productList){
			return collect($items)->map(function($item, $key) use($productList) {
				
				#轉換成系統設定Id and Name
				$product = data_get($productList, $item['erpNo'], NULL);
				
				$temp['productId']	= empty($product) ? 0 : $product['productId'];
				$temp['productName']= empty($product) ? '' : $product['productName'];
				$temp['amount']		= $item['amount'];	
				$temp['qty']		= $item['qty'];
				$temp['saleDate']	= $item['saleDate'];
				
				return $temp;
			})->toArray();
			
		})->toArray();
		
		$baseData = collect($params->storeList)->map(function($item, $key) use($saleData) {
			
			$data = data_get($saleData, $item['posId'], []); 
			
			$temp['storeKey'] 	= $item['storeKey'];
			$temp['shopId'] 	= $item['posId']; 
			$temp['storeName'] 	= $item['storeName'];
			$temp['areaId'] 	= $item['areaId'];
			$temp['areaName']	= $item['areaName'];
			$temp['data']		= $data;
			
			return $temp;
		})->toArray();
		
		$params->baseData = $baseData;
	}
	
	/* ====================== Prepare data End ====================== */
	
	
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
			#1.Date list
			$this->_buildDayRange($params);
			
			#2.區域
			$this->_parsingArea($params);
			
			#3.店別
			$this->_parsingStore($params);
			
			return TRUE;
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
	
	/* 區域彙總
	 * @params: array
	 * @params: int
	 * @return: array
	 */
	private function _parsingArea($params)
	{
		/* Output
		"area" => [
			"大台北區" => [
				"totalQty" => 101
				"totalAmount" => 101
				"products" => productId => [
					'totalQty' => ..
					'totalAmount' => ..
				], ....
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
		
		$header = [
					'areaName' 	=> '區域', 
					'storeCount'=> '店家數',
					'products' 	=> $params->productMap
				];
		$params->set('area.header', $header);
		
		$result = collect($baseData)->groupBy('areaId')->map(function($items, $key) {
			
			#區域總計
			$temp['areaName'] 	= $items->pluck('areaName')->first();
			$temp['storeCount']	= $items->count(); #店家數
			
			$data = $items->pluck('data')->filter()->collapse();
			
			$temp['products'] = collect($data)->groupBy('productId')->map(function($items, $key){
				
				$temp['totalQty'] 	= $items->pluck('qty')->sum();
				$temp['totalAmount']= round($items->pluck('amount')->sum(), 2);
				
				return $temp;
			})->toArray();
			
			return $temp;
		})->toArray();
		
		#這裏是依header
		$result['total']['areaName']	= '全區合計';
		$result['total']['storeCount']	= collect($baseData)->count(); 
		
		$totalData = collect($baseData)->pluck('data')->collapse();
		$result['total']['products'] 	= collect($totalData)->groupBy('productId')->map(function($items, $key){
				
			$temp['totalQty'] 	= $items->pluck('qty')->sum();
			$temp['totalAmount']=  round($items->pluck('amount')->sum(), 2);
			
			return $temp;
		})->toArray();
		
		$params->set('area.data', array_filter($result));
	}
	
	/* 店別每日銷售
	 * @params: array
	 * @params: int
	 * @return: array
	 */
	private function _parsingStore($params)
	{
		/* 重整資料格式
		array:6 [
			"storeKey" => "2700005"
			"shopId" => "100001"
			"storeName" => "御廚中正南昌店"
			"areaId" => 1
			"areaName" => null
			"products" => [
				92 => array:2 [
				  "totalQty" => 2
				  "totalAmount" => 90.0
				]
			]
		]
		*/
		$params->set('store.header', []);
		$params->set('store.data', []);
		
		$baseData 	= $params->baseData;
		$dayRange 	= $params->dayRange;
		$productMap = $params->productMap; #key-value
		
		#會有無設定區域權限的狀況, 須判別
		if (empty($baseData))
			return FALSE;
		
		#array_merge key不會保留
		$header = [
			'areaName'	=> '區域', 
			'shopId'	=> 'POS店號',
			'storeKey'	=> '門店代號', 
			'storeName'	=> '門店名稱',
			'products' 	=> $productMap
		];
		
		$params->set('store.header', $header);
		
		#總量不分Date
		$result = collect($baseData)->sortBy('areaId')->map(function($item, $key) use($dayRange, $productMap) {
			
			$temp['storeKey'] 	= $item['storeKey'];
			$temp['shopId'] 	= $item['shopId'];
			$temp['storeName'] 	= $item['storeName'];
			$temp['areaId'] 	= $item['areaId'];
			$temp['areaName'] 	= $item['areaName'];
			
			$temp['products'] = collect($item['data'])->groupBy('productId')->map(function($items, $key){
				
				$temp['totalQty'] 	= $items->pluck('qty')->sum();
				$temp['totalAmount']=  round($items->pluck('amount')->sum(), 2);
				
				return $temp;
				
			})->toArray();
			
			$temp['details'] = $this->_parsingDetail($item['data'], $dayRange, $productMap);
			
			return $temp;	
		})->values()->all();
		
		$params->set('store.data', $result);
	}
	
	/* 逐日銷售by store
	 * @params: array
	 * @params: int
	 * @return: array
	 */
	private function _parsingDetail($productData, $dayRange, $productMap)
	{
		#須存成無key狀態,順序才不會受影響
		$details = [];
		$details['header'] = collect(['品名'])->merge($dayRange)->values()->all();
		
		$productGroup = collect($productData)->groupBy('productId')->map(function($items, $key) {
			return collect($items)->groupBy('saleDate')->map(function($items, $key){
				$temp['qty'] 	= $items->pluck('qty')->sum();
				$temp['amount']	= round($items->pluck('amount')->sum(), 2);
				
				return $temp;
			})->all();
		})->all();
		
		#處理無值也有顯示
		foreach($productMap as $id => $name)
		{
			$data = data_get($productGroup, $id, NULL);
			
			#按header順序
			$row = [];
			$row['qty'][] 	= $name;
			$row['amount'][]= $name;
			
			foreach($dayRange as $date)
			{
				$row['qty'][] 	= data_get($data, "{$date}.qty", 0);
				$row['amount'][]= data_get($data, "{$date}.amount", 0);
			}
			
			$details['data'][] = $row;
		}
		
		return $details;
	}
	
	/* 逐日銷售by store
	 * @params: array
	 * @params: int
	 * @return: array
	 */
	/* public function parsingDetail($params)
	{
		$params->set('detail.header', []);
		$params->set('detail.data', []);
		$baseData 	= $params->baseData;
		$productMap	= $params->productMap;
		
		$this->_buildDayRange($params);
		
		#會有無設定區域權限的狀況, 須判別
		if (empty($baseData))
			return FALSE;
		
		$dayRange 	= $params->dayRange;
		$header		= collect(['品名'])->merge($dayRange)->values()->all();
		$params->set('detail.header', $header);
		
		#Deatail by day(base data是全門店資料)
		$result = collect($baseData)->mapWithKeys(function($item, $key){
			#顯示時以storeKey當關聯key
			return [$item['storeKey'] => $item];
		})->map(function($item, $key) use($productMap, $dayRange) {
			
			$temp['storeKey'] 	= $item['storeKey'];
			$temp['storeName'] 	= $item['storeName'];
			
			$temp['products'] =  collect($item['data'])->groupBy('productId')->map(function($items, $key) use($dayRange) {
				
				$dayItems = $items->groupBy('saleDate');
				
				#按日期順序
				return collect($dayRange)->map(function($date, $key) use($dayItems){
					
					$data = $dayItems->get($date, collect([]));
					
					if ($data->isEmpty())
					{
						$temp['totalQty'] 	= 0;
						$temp['totalAmount']= 0;
					}
					else
					{	
						$temp['totalQty'] 	= $data->pluck('qty')->sum();
						$temp['totalAmount']= round($data->pluck('amount')->sum(), 2);
					}
					
					return $temp;
				});
			
			})->mapWithKeys(function($item, $key) use($productMap) {
				$productName = data_get($productMap, $key);
				return [$productName => $item];
			})->toArray();
			
			return $temp;	
		})->all();
		
		$params->set('detail.data', $result);
	} */
	
	/* Export data
	 * @params: enum
	 * @params: date
	 * @params: date
	 * @return: array
	 */
	public function export($sourceData)
	{
		try
		{
			#Build export data
			list($export['區域彙總-數量'], $export['區域彙總-金額']) = $this->_buildAreaExport($sourceData['area']);
			list($export['店別明細-數量'], $export['店別明細-金額']) = $this->_buildStoreExport($sourceData['store']);
			
			#Write export to file
			$brandName = Brand::tryFrom($sourceData['brandId'])->label();
			$fileName = Str::replaceArray('?', [$brandName, $sourceData['startDate'], $sourceData['endDate']], '?_銷售_?_?.xlsx');
			$filePath = Storage::disk('export')->path($fileName);
			
			$writer = new Writer();
			$writer->openToFile($filePath);
			
			$centerStyle = (new Style())->setCellAlignment(CellAlignment::CENTER);
			
			foreach($export as $sheetName => $sheetData)
			{
				$sheet = ($sheetName == '區域彙總-數量') ? $writer->getCurrentSheet() : $writer->addNewSheetAndMakeItCurrent();
				$sheet->setName($sheetName);
				
				foreach($sheetData as $data)
				{
					$row =  Row::fromValues($data, $centerStyle);
					$writer->addRow($row);
				}
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
	private function _buildAreaExport($areaData)
	{
		#標頭都相同, 但要產生數量及金額兩個sheets
		$export['areaQty'] 		= [];
		$export['areaAmount'] 	= [];
		
		$header = Arr::flatten($areaData['header']);
		
		#Header相同
		$export['areaQty'][]	= $header;
		$export['areaAmount'][] = $header;
		
		foreach($areaData['data'] as $areaId => $data)
		{
			$rowQty		= [];
			$rowAmount 	= [];
			
			$rowQty[]	 = $data['areaName'];
			$rowAmount[] = $data['areaName'];
			
			$rowQty[]	 = $data['storeCount'];
			$rowAmount[] = $data['storeCount'];
			
			#須依header的順序取資料
			foreach($areaData['header']['products'] as $productId => $productName)
			{
				$rowQty[]	= intval(data_get($data, "products.{$productId}.totalQty", 0));
				$rowAmount[]= Number::currency(intval(data_get($data, "products.{$productId}.totalAmount", 0)), precision: 0);
			}
			
			$export['areaQty'][]	= $rowQty;
			$export['areaAmount'][] = $rowAmount;
		}
		
		return [$export['areaQty'], $export['areaAmount']] ;
	}
	
	/* Build data for export
	 * @params: array
	 * @return: array
	 */
	private function _buildStoreExport($shopData)
	{
		#標頭都相同, 但要產生數量及金額兩個sheets
		$export['shopQty'] 		= [];
		$export['shopAmount'] 	= [];
		
		$header = Arr::flatten($shopData['header']);
		
		#Header相同
		$export['shopQty'][]	= $header;
		$export['shopAmount'][] = $header;
		
		foreach($shopData['data'] as $index => $data)
		{
			$rowQty		= [];
			$rowAmount 	= [];
			
			$rowQty[]	= $data['areaName'];
			$rowQty[]	= $data['shopId'];
			$rowQty[]	= $data['storeKey'];
			$rowQty[]	= $data['storeName'];
			
			$rowAmount[]= $data['areaName'];
			$rowAmount[]= $data['shopId'];
			$rowAmount[]= $data['storeKey'];
			$rowAmount[]= $data['storeName'];
			
			foreach($shopData['header']['products'] as $productId => $productName)
			{
				$rowQty[]	= intval(data_get($data, "products.{$productId}.totalQty", 0));
				$rowAmount[]= Number::currency(intval(data_get($data, "products.{$productId}.totalAmount", 0)), precision: 0);
			}
			
			$export['shopQty'][]	= $rowQty;
			$export['shopAmount'][] = $rowAmount;
		}
		
		return [$export['shopQty'], $export['shopAmount']] ;
	}
}
