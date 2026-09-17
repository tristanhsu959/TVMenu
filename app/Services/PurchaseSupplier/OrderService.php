<?php

namespace App\Services\PurchaseSupplier;

use App\Facades\AppManager;
use App\Facades\PurchaseManager;
use App\Facades\StoreManager;
use App\Repositories\PurchaseSupplierRepository;
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
use OpenSpout\Common\Entity\Style\Style; 
use OpenSpout\Common\Entity\Style\CellAlignment;

#partial Service
class OrderService
{
	private $_statistics	= [];
   
	public function __construct(protected PurchaseSupplierRepository $_repository)
	{
		$this->_statistics = [
			'type'			=> '',
			'where'			=> '',
			'brandId'		=> '', #export
			'brandCode'		=> '', 
			'startDate'		=> '', #Y-m-d
            'endDate'   	=> '',
			'dateList'		=> [],
			'productList'	=> [],
			'storeList'		=> [],
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
	 * @params: object
	 * @return: array
	 */
	private function _generateStatistics($params)
	{
		$this->_statistics['type']			= $params->type;
		$this->_statistics['brandId']		= $params->brand->value; 
		$this->_statistics['brandCode']		= $params->brand->code(); 
		$this->_statistics['startDate'] 	= $params->stDate; 
		$this->_statistics['endDate'] 		= $params->endDate;
		$this->_statistics['dateList'] 		= $params->dateList;
		$this->_statistics['productList'] 	= $params->productList;
		$this->_statistics['storeList'] 	= $params->storeList;
		$this->_statistics['data'] 			= $params->data;
		$this->_statistics['hasResult'] 	= FALSE;
		
		#無值不cache
		if (! empty($params->data))
		{
			$this->_statistics['hasResult'] 	= TRUE;
			$this->_statistics['exportToken'] 	= bin2hex($params->cacheKey); #hex2bin
			
			$this->_statistics['exportName'] = '供應商出貨總量';
			Cache::put($params->cacheKey, $this->_statistics, now()->addMinutes(10));
		}
	}
	
	/* ====================== 主流程 End ====================== */
	
	/* 取統計相關參數
	 * @params: enums
	 * @params: integer
	 * @return: array
	 */
	private function _prepareData($params)
	{
		try
		{
			$this->_getStoreList($params);
			
			$this->_getProductIdByName($params);
			
			$this->_getDataFromDB($params);
			
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
	private function _getProductIdByName($params)
	{
		try
		{
			#取資料都不分營運中心,不然可能會取不到
			#此功能只有Keyword需要取Id
			if ($params->where == 'keyword')
				$params->productIds = PurchaseManager::getSupplierProductIdByName($params->keyword);
			
			if (empty($params->productIds))
				throw new Exception('查無此產品');
			
			#Convert to integer
			$params->productIds = collect($params->productIds)->map(function($item, $key){
				return (int)$item;
			})->all();
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception($e->getMessage());
		}
	}
	
	/* Get order data
	 * @params: 
	 * @return: array
	 */
	private function _getDataFromDB($params)
	{
		/*
		[
			"expectedDate" => "2026-08-09"
			"storeId" => "2885"
			"productId" => "8012"
			"productName" => "福壽耐炸油"
			"unit" => "桶"
			"qty" => "4"
			"amount" => "3480.000000"
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
			
			#區域權限由門店過濾
			$orderData = $this->_repository->getOrderDataByProductId($brand, $allowOpCenterIds, $allowAreaIds, $stDate, $endDate, $productIds);
			
			$params->orderData = $orderData;
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('讀取供應商訂單資料失敗');
		}
	}
	
	/* 基底資料
	 * @params: collection
	 * @return: array
	 */
	private function _buildBaseData($params)
	{
		#供應商訂單用Id mapping
		$authStoreIds = collect($params->storeList)->pluck('storeId')->unique()->all();
		
		#用門店再過一次,以防萬一
		$baseData = collect($params->orderData)->filter(function($item, $key) use($authStoreIds){
			return in_array($item['storeId'], $authStoreIds);
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
			#1.計算查詢範圍總天數 (use Date not DateTime)
			$this->_buildDateHeader($params);
			
			#2.Build productList
			$this->_buildProductList($params);
		
			#3. analysis by 門店
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
	private function _buildDateHeader($params)
	{
		$header 	= [];
		
		#By day
		$st		= Carbon::create($params->stDate);
		$end 	= Carbon::create($params->endDate);
		$period = CarbonPeriod::create($st, $end);
			
		foreach ($period as $date) 
		{
			$header[] = $date->format('Y-m-d');
		}
		
		$params->dateList = $header;
	}
	
	/* Get order data
	 * @params: array
	 * @return: array
	 */
	private function _buildProductList($params)
	{
		#改用查詢Id取清單(無資料也會顯示Product)
		$productList = PurchaseManager::getSupplierProductListByIds($params->productIds);
		
		$params->productList = $productList;
	}
	
	/* 依Store
	 * @params: array
	 * @return: array
	 */
	private function _parsingByStore($params)
	{
		/*
		"ProductId" => array:2 [
			"StoreId" => array:2 [
				"2026-03-25" => array:1 [
					"qty" => 116
				]
				"2026-03-26" => array:1 []
			]
		]
		*/
		
		$orderData = $params->baseData;
		
		if (empty($orderData))
		{
			$params->data = [];
			return;
		}
		
		$result = collect($orderData)->groupBy('productId')->map(function($items, $key) {
			
			#By storeId
			$temp = $items->groupBy('storeId')->map(function($items, $key) {
				
				return $items->groupBy('expectedDate')->map(function($items, $key) {
					$temp['qty'] 	= round($items->pluck('qty')->sum(), 2);
					$temp['amount'] = round($items->pluck('amount')->sum(), 2);
					
					return $temp;
				})->all();
				
			})->all(); 
			
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
			$fileName = Str::replaceArray('?', [$brandName, $sourceData['exportName'], $sourceData['startDate'], $sourceData['endDate']], '?_?_?_?.xlsx');
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
		$header = array_merge(['區域', 'POS店號', '門店代號', '門店名稱'], $sourceData['dateList']);
		
		#每個product要一個sheet
		foreach($sourceData['productList'] as $productId => $productName)
		{
			$storeData = data_get($sourceData['data'], $productId, []);
			
			if (empty($storeData))
				continue;
			
			$export[$productName] = [];
			$export[$productName][] = $header;
			
			#使用header來控制顯示順序,先TP後KH
			foreach($sourceData['storeList'] as $index => $store)
			{
				$row = [];
				$row[] = $store['areaName'];
				$row[] = $store['posId'];
				$row[] = $store['storeKey'];
				$row[] = $store['storeName'];
				
				$storeId = $store['storeId'];
				
				#要按Header的順序
				foreach($sourceData['dateList'] as $date)
				{
					$row[] = data_get($storeData, "{$storeId}.{$date}.qty", 0);
				}
				
				$export[$productName][] = $row;
			}
		}
		
		return $export;
	}
}
