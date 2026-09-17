<?php

namespace App\Services\PurchaseReport;

use App\Facades\AppManager;
use App\Facades\PurchaseManager;
use App\Facades\StoreManager;
use App\Facades\LegacyManager;
use App\Repositories\PurchaseReportRepository;
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
use Illuminate\Support\Number;
use Carbon\CarbonPeriod;
use Exception;
use OpenSpout\Writer\Common\Creator\WriterEntityFactory;
use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;

#partial Service
class ExtraOrderService
{
	private $_statistics	= [];
   
	public function __construct(protected PurchaseReportRepository $_repository)
	{
		$this->_statistics = [
			'type'		=> '',
			'brandId'		=> '', #export
			'brandCode'		=> '', 
			'startDate'		=> '', #Y-m-d
            'endDate'   	=> '',
			'extraOrder'	=> [],
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
		$this->_statistics['extraOrder'] 	= $params->extraOrder;
		$this->_statistics['hasResult'] 	= FALSE;
		
		#無值不cache
		if (! empty($params->extraOrder['data']))
		{
			$this->_statistics['hasResult'] 	= TRUE;
			$this->_statistics['exportToken'] 	= bin2hex($params->cacheKey); #hex2bin
			
			$this->_statistics['exportName'] = '追加';
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
			#1.Get store
			$this->_getStoreList($params);
			
			#2.舊系統追加訂單
			$extraData = $this->_getExtraOrderDataFromDB($params);
			
			#3. Build base data
			#會有false的無效array, 用array_filter去除
			$this->_buildBaseData($params, array_filter($extraData));
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
		
		$params->storeList = $storeList;
	}
	
	/* Get order data
	 * @params: 
	 * @return: array
	 */
	private function _getExtraOrderDataFromDB($params)
	{
		/*0 => [
			"orderNo" => "2026030810003"
			"orderDate" => "2026-03-08 09:30:04"
			"memo" => "嘉一香(已付)"
			"shortCode" => "0201"
			"productName" => "白豆漿"
			"unit" => "包"
			"price" => "40.00"
			"qty" => "3.00"
			"totalAmount" => "120.00"
			"factoryNo" => "TW_TP"
			"factoryName" => "淡水總廠"
		]
		*/
	
		try
		{
			$brand 				= $params->brand;
			$stDate				= (new Carbon($params->stDate))->format('Y-m-d 00:00:00');
			$endDate 			= (new Carbon($params->endDate))->addDay()->format('Y-m-d H:i:s');
			$allowOpCenterIds 	= $params->allowOpCenterIds;
			$allowAreaIds 		= $params->allowAreaIds;
			
			#舊系統只分工廠, 不分區域權限
			$factoryList = PurchaseManager::getFactoryList($brand, $allowOpCenterIds);
			$factoryNos = array_keys($factoryList);
			
			#直接取舊系統,不取Local(因呈現方式不同)
			$extraData = LegacyManager::getFullExtraData($brand, $stDate, $endDate, $factoryNos);
			
			return $extraData;
			
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('讀取公關員購訂單資料失敗');
		}
	}
	
	
	/* 基底資料
	 * @params: collection
	 * @return: array
	 */
	private function _buildBaseData($params, $extraData)
	{
		#因無areaId, 故只能從門店過濾
		$validStoreKeys = collect($params->storeList)->pluck('storeKey')->values()->all();
			
		$baseData = collect($extraData)->map(function($item, $key){
			$item['storeKey'] = StoreManager::buildStoreKey($item['storeNo']);
			return $item;
		})->filter(function($item, $key) use($validStoreKeys) {
			return in_array($item['storeKey'], $validStoreKeys);
		})->groupBy('storeKey')->toArray();
		
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
			#1.追加
			$this->_parsing($params);
			
			return $params;
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('解析報表資料發生錯誤');
		}
	}
	
	/* Format output
	 * @params: array
	 * @return: array
	 */
	private function _parsing($params)
	{
		/*[
			"areaName" => "大高雄區"
			"storeKey" => "8120003"
			"storeName" => "高雄小港店"
			"orderDate" => "2026-06-03"
			"orderNo" => null
			"productName" => null
			"shortCode" => null
			"unit" => null
			"price" => null
			"qty" => null
			"totalAmount" => "1080.00"
			"factoryName" => "高雄工廠"
			"memo" => null
			"items" => array:2 [
				0 => array:9 [▼
					"orderNo" => "2026060310004"
					"orderDate" => "2026-06-03 11:08:39"
					"memo" => "追加"
					"shortCode" => "0201"
					"productName" => "白豆漿"
					"unit" => "包"
					"price" => "40.00"
					"qty" => "12.00"
					"totalAmount" => "480.00"
				]
				1 => array:9 [▶]
		*/
	  
		$header = ['區域', '門店代號', '門店名稱', '訂單日期', '訂單編號', '產品名稱', '簡碼', '單位', '單價', '數量', '總金額', '出貨工廠', '備註'];
		$params->set('extraOrder.header', $header);
		
		$baseData = $params->baseData;
		$allOrderStoreKeys = array_keys($baseData);
		
		#綁定門店, 只顯示有訂單門店
		#過濾有訂單的門店(只是為了避免排序亂掉)
		$result = collect($params->storeList)->filter(function($item, $key) use($allOrderStoreKeys) {
			return in_array($item['storeKey'], $allOrderStoreKeys);
		})->map(function($item, $key) use($baseData) {
			
			$orderData = collect(data_get($baseData, $item['storeKey'], []));
			
			if (empty($orderData))
				return '';
			
			$temp['areaName'] 		= $item['areaName'];
			$temp['storeKey'] 		= $item['storeKey'];
			$temp['storeName'] 		= $item['storeName'];
			$temp['orderDate']		= Carbon::parse($orderData->pluck('orderDate')->first())->format('Y-m-d');
			$temp['orderNo'] 		= NULL;
			$temp['productName']	= NULL;
			$temp['shortCode']		= NULL;
			$temp['unit']			= NULL;
			$temp['price']			= NULL;
			$temp['qty']			= NULL;
			$temp['totalAmount'] 	= $orderData->pluck('orderAmount')->first();
			$temp['factoryName']	= $item['factoryName'];
			$temp['memo']			= NULL;
			
			$temp['items'] = $orderData->map(function($item, $key){
				unset($item['storeKey']);
				unset($item['storeNo']);
				unset($item['orderAmount']);
				unset($item['factoryNo']);
				unset($item['factoryName']);
				return $item;
			})->all();
			
			return $temp;
		})->values()->all();
		
		$params->set('extraOrder.data', $result);
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
			$export['追加'] = $this->_buildExportData($sourceData['extraOrder']);
			
			#Write export to file
			$brandName = Brand::tryFrom($sourceData['brandId'])->label();
			$fileName = Str::replaceArray('?', [$brandName, $sourceData['exportName'], $sourceData['startDate'], $sourceData['endDate']], '?_?_?_?.xlsx');
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
	private function _buildExportData($orderData)
	{
		$export = [];
		$export[] = $orderData['header'];
		
		#每個product要一個sheet
		foreach($orderData['data'] as $order)
		{
			$row = [];
			
			$row[]	= $order['areaName'];
			$row[]	= $order['storeKey'];
			$row[]	= $order['storeName'];
			
			$row[]	= $order['orderDate'];
			$row[] 	= $order['orderNo'];
			$row[]	= $order['productName'];
			$row[]	= $order['shortCode'];
			$row[]	= $order['unit'];
			$row[]	= $order['price'];
			$row[]	= $order['qty'];
			$row[]	= empty($order['totalAmount']) ? '' : Number::currency($order['totalAmount'], precision: 2);
			$row[]	= $order['factoryName'];
			$row[]	= $order['memo'];
			
			$export[] = $row;
			
			foreach($order['items'] as $item)
			{
				$row = [];
				
				$row[]	= data_get($item, 'areaName', '');
				$row[]	= data_get($item, 'storeKey', '');
				$row[]	= data_get($item, 'storeName', '');
				
				$row[]	= $item['orderDate'];
				$row[] 	= $item['orderNo'];
				$row[]	= $item['productName'];
				$row[]	= $item['shortCode'];
				$row[]	= $item['unit'];
				$row[]	= empty($item['price']) ? 0 : Number::currency($item['price'], precision: 2);
				$row[]	= $item['qty'];
				$row[]	= empty($item['totalAmount']) ? 0 : Number::currency($item['totalAmount'], precision: 2);
				$row[]	= data_get($item, 'factoryName', '');
				$row[]	= $item['memo'];
				
				$export[] = $row;
			}
		}
		
		return $export;
	}
}
