<?php

namespace App\Services\PurchaseSales;

use App\Facades\AppManager;
use App\Facades\PurchaseManager;
use App\Facades\LocalLegacyManager;
use App\Repositories\PurchaseSalesRepository;
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
use Exception;
use OpenSpout\Writer\Common\Creator\WriterEntityFactory;
use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;

#partial Service
class OrderService
{
	private $_statistics	= [];
   
	public function __construct(protected PurchaseSalesRepository $_repository)
	{
		$this->_statistics = [
			'brandId'		=> '', 
			'brandCode'		=> '', 
			'searchDate'	=> '', #Y-m-d
			'storeInfo' 	=> '',
            'purchaseData' 	=> [],
			'saleData'		=> [],
			'exportToken'	=> '',
		];
	}
	
	/* ====================== 主流程 ====================== */
	/* Search data
	 * @params: array
	 * @return: array
	 */
	public function analysis($brand, $functions, $searchDate, $searchStoreId)
	{
		try
		{
			#因不同邏輯,故init params放在child service
			$params = $this->_initParams($brand, $functions, $searchDate, $searchStoreId);
			
			#此功能暫不cache
			#暫cache for testing
			if (Cache::has($params->cacheKey))
			{
				Log::channel('appServiceLog')->info('Get purchase & sales data from cache');
				
				$statistics = Cache::get($params->cacheKey); #cache data is response format
				return $statistics;
			}
			else 
			{
				Log::channel('appServiceLog')->info('Get purchase & sales data from db');
				
				#Prepare data(object default called by reference)
				$this->_prepareData($params);
			
				#Statistics
				$this->_outputReport($params);
				
				#Create output to var statistics
				$this->_generateStatistics($params);
				
				return $this->_statistics;
			}
		}
		catch(Exception $e)
		{
			throw new Exception($e->getMessage());
		}
	}
	
	/* Init input params
	 * @params: enums
	 * @params: integer
	 * @params: string
	 * @params: string
	 * @return: array
	 */
	private function _initParams($brand, $functions, $searchDate, $searchStoreId)
	{
		$params = new Fluent();
		
		$currentUser = AppManager::getCurrentUser();
		$userAreaIds = $currentUser->roleArea;
		
		$cacheKey 	= HelperLib::buildCacheKey([$functions, $userAreaIds, $searchDate, $searchStoreId]);
		
		$params->brand($brand)->userAreaIds($userAreaIds)
				->searchDate($searchDate)->searchStoreId($searchStoreId)
				->cacheKey($cacheKey);
		
		return $params;
	}
	
	/* Generate statistics data
	 * @params: object
	 * @return: array
	 */
	private function _generateStatistics($params)
	{
		$this->_statistics['brandId']		= $params->brand->value;
		$this->_statistics['brandCode']		= $params->brand->code();
		$this->_statistics['searchDate'] 	= $params->searchDate;
		$this->_statistics['storeInfo'] 	= $params->storeInfo;
		$this->_statistics['purchaseData']	= $params->purchaseData;
		$this->_statistics['saleData']		= $params->saleData;
		
		#無值不cache
		if (! empty($params->purchaseData['data']) OR ! empty($params->saleData['data']))
		{
			$this->_statistics['exportName']	= Str::replaceArray('?', [$params->storeInfo['storeName'], $params->searchDate], '?_訂貨及銷售資訊_?');;
			$this->_statistics['exportToken'] 	= bin2hex($params->cacheKey); 
			Cache::put($params->cacheKey, $this->_statistics, now()->addMinutes(20));
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
			#1.Get product id(必須先執行)
			$this->_getStoreParams($params);
			
			#2.Get purchase order
			$this->_getPurchaseOrderFromDB($params);
			
			#3.Get pos order
			$this->_getSaleOrderFromDB($params);
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
	private function _getStoreParams($params)
	{
		try
		{
			$info = $this->_repository->getPurchaseStoreInfoById($params->searchStoreId);
			
			$info['storeKey'] = PurchaseManager::buildStoreKey($info['storeNo']);
			$params->set('storeInfo', $info);
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
	private function _getPurchaseOrderFromDB($params)
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
			$brand 		= $params->brand;
			$stDate		= (new Carbon($params->searchDate))->format('Y-m-d 00:00:00');
			$endDate 	= (new Carbon($params->searchDate))->addDay()->format('Y-m-d H:i:s');
			$storeId	= $params->searchStoreId;
			$storeKey	= $params->storeInfo['storeKey'];
			
			#已包含蘿蔔訂單(因為是單店,區域權限在list已過濾)
			$orderData = $this->_repository->getPurchaseOrderByStore($brand, $stDate, $endDate, $storeId);
			
			#舊系統用storeKey = accno
			$extraData = LocalLegacyManager::getExtraDataByStore($brand, $stDate, $endDate, $storeKey);
			
			#整合追加資料
			$baseData = collect(array_filter($orderData))->merge(array_filter($extraData));
			
			#處理包裝轉換
			$baseData = $baseData->map(function($item, $key){
				$item['qty'] 	= round(intval($item['qty']) * PurchaseManager::getPackagingScale($item['shortCode']), 2);
				$item['amount'] = round($item['amount'], 2);
				return $item;
			})->toArray();
			
			$params->purchaseBaseData = $baseData;
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('讀取訂貨系統訂單資料失敗');
		}
	}
	
	/* Get order data
	 * @params: array
	 * @return: array
	 */
	private function _getSaleOrderFromDB($params)
	{
		/* [
			"erpNo" => "PS05000016"
			"productName" => "黃金泡菜"
			"price" => "30"
			"qty" => "1"
			"discount" => ".0000"
		]
		*/
		try
		{
			$brand 		= $params->brand;
			$stDate		= (new Carbon($params->searchDate))->format('Y-m-d 00:00:00');
			$endDate 	= (new Carbon($params->searchDate))->addDay()->format('Y-m-d H:i:s');
			$posId		= $params->storeInfo['posId'];
			
			#因為是單店,區域權限在list已過濾
			$orderData = $this->_repository->getPosOrderByPosId($brand, $stDate, $endDate, $posId);
			
			#格式化成baseData, 先正規化變數
			$baseData = collect($orderData)->map(function($item, $key){
				
				$temp['erpNo']		= $item['erpNo'];
				$temp['productName']= $item['productName'];
				$temp['qty'] 		= intval($item['qty']); 
				#只先處理單項的amount
				$temp['amount'] 	= round(($item['qty'] * $item['price']) + $item['discount'], 2);
				
				return $temp;
			})->toArray();
			
			$params->saleBaseData = $baseData;
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('讀取Pos系統訂單資料失敗');
		}
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
			#1.訂貨統計
			$this->_parsingByPurchase($params);
			
			#2.銷售統計
			$this->_parsingBySale($params);
			
			return $params;
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('解析報表資料發生錯誤');
		}
	}
	
	/* 依訂貨
	 * @params: array
	 * @return: array
	 */
	private function _parsingByPurchase($params)
	{
		$orderData = $params->purchaseBaseData;
		
		if (empty($orderData))
			return [];
		
		$params->set('purchaseData.header', ['產品代碼', '產品名稱', '數量', '金額', '備註']);
		
		#分群定義不同
		$result = collect($orderData)->groupBy('shortCode')->map(function($items, $key) {
			$temp['shortCode'] 	= $items->pluck('shortCode')->first();
			$temp['productName']= $items->pluck('productName')->first();
			$temp['qty'] 		= intval($items->pluck('qty')->sum());
			$temp['amount'] 	= round($items->pluck('amount')->sum(), 2);
			$temp['memo'] 		= $items->pluck('memo')->first();
			
			return $temp;
		});
		
		$total['shortCode'] 	= '';
		$total['productName']	= '總計';
		$total['qty'] 			= intval($result->pluck('qty')->sum());
		$total['amount'] 		= round($result->pluck('amount')->sum(), 2);
		$total['memo'] 			= '';
		
		$result = $result->sortBy('shortCode')->push($total)->values()->all();
		$params->set('purchaseData.data', $result);
	}
	
	/* 依銷售
	 * @params: array
	 * @return: array
	 */
	private function _parsingBySale($params)
	{
		$orderData = $params->saleBaseData;
		
		if (empty($orderData))
			return [];
		
		$params->set('saleData.header', ['產品料號', '產品名稱', '數量', '金額']);
		
		#這裏才處理分群不同,先針對erpNo group(之後若需要,要group by dashboard product)
		$result = collect($orderData)->groupBy('erpNo')->map(function($items, $key){
				$temp['erpNo']		= $items->pluck('erpNo')->first();
				$temp['productName']= $items->pluck('productName')->first();
				$temp['qty'] 		= intval($items->pluck('qty')->sum()); #qty直接加總
				$temp['amount'] 	= round($items->pluck('amount')->sum(), 2);
				
				return $temp;
			});
		
		$total['erpNo'] 		= '';
		$total['productName']	= '總計';
		$total['qty'] 			= intval($result->pluck('qty')->sum());
		$total['amount'] 		= round($result->pluck('amount')->sum(), 2);
		
		$result = $result->sortBy('erpNo')->push($total)->values()->all();
		$params->set('saleData.data', $result);
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
		