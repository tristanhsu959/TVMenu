<?php

namespace App\Services\Shipments;

use App\Facades\AppManager;
use App\Facades\PurchaseManager;
use App\Facades\StoreManager;
use App\Facades\LocalLegacyManager; #from local
use App\Repositories\ShipmentsRepository;
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
class StatusService
{
	private $_statistics	= [];
   
	public function __construct(protected ShipmentsRepository $_repository)
	{
		$this->_statistics = [
			'type'			=> '',
			'by'			=> '',
			'calc'			=> '',
			'brandId'		=> '', #export
			'brandCode'		=> '', 
			'startDate'		=> '', #Y-m-d
            'endDate'   	=> '',
			'dateList'		=> [],
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
		$this->_statistics['by']			= $params->by; 
		$this->_statistics['calc']			= $params->calc; 
		$this->_statistics['brandId']		= $params->brand->value; 
		$this->_statistics['brandCode']		= $params->brand->code(); 
		$this->_statistics['startDate'] 	= $params->stDate; 
		$this->_statistics['endDate'] 		= $params->endDate;
		$this->_statistics['dateList'] 		= $params->dateList;
		$this->_statistics['data'] 			= $params->data;
		$this->_statistics['hasResult'] 	= FALSE;
		
		#無值不cache
		if (! empty($params->data))
		{
			$this->_statistics['hasResult'] 	= TRUE;
			$this->_statistics['exportToken'] 	= FALSE; #bin2hex($params->cacheKey); #hex2bin
			$this->_statistics['exportName'] 	= '門店訂貨狀況';
			
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
			
			$this->_getStoreIdByName($params);
			
			$this->_getDataFromDB($params);
			
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
	
	/* 取查詢的門店資料
	 * @params: collection
	 * @return: array
	 */
	private function _getStoreIdByName($params)
	{
		#避免用like查詢
		$storeName = $params->storeName;
		
		if (empty($storeName))
		{
			$params->nameStoreIds 	= []; #新系統
			$params->nameStoreKeys 	= []; #舊系統
			return TRUE;
		}
		
		$storeList = collect($params->storeList)->filter(function($item, $key) use($storeName){
			return Str::contains($item['storeName'], $storeName);
		});
		
		#有查店名,要更新store list
		$params->storeList 	= $storeList->toArray();
		$params->nameStoreIds	= $storeList->pluck('storeId')->values()->all(); 
		$params->nameStoreKeys	= $storeList->pluck('storeKey')->values()->all(); 
		
		if (empty($params->storeList))
			throw new Exception('查無符合門店資料');
	}
	
	/* Get order data
	 * @params: 
	 * @return: array
	 */
	private function _getDataFromDB($params)
	{
		/*0 => array:12 [
			"expectedDate" => "2026-05-29"
			"storeNo" => "KH4010002"
			"qty" => "90"
			"amount" => "6300.000000"
			"productName" => "招牌餡"
			"erpNo" => "PR00208001"
			"shortCode" => "0001"
		]
		*/
	
		try
		{
			$brand 				= $params->brand;
			$stDate				= (new Carbon($params->stDate))->format('Y-m-d 00:00:00');
			$endDate 			= (new Carbon($params->endDate))->addDay()->format('Y-m-d H:i:s');
			$storeIds			= $params->nameStoreIds;
			$allowOpCenterIds 	= $params->allowOpCenterIds;
			$allowAreaIds 		= $params->allowAreaIds;
			
			#已包含蘿蔔訂單
			$orderData = $this->_repository->getOrderDataByStore($brand, $allowOpCenterIds, $allowAreaIds, $stDate, $endDate, $storeIds);
			
			$params->orderData = $orderData;
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
			$storeKeys			= $params->nameStoreKeys;
			$allowOpCenterIds 	= $params->allowOpCenterIds;
			$allowAreaIds 		= $params->allowAreaIds;
			
			#維持原狀,不判別opCenter, 最後由門店一起過濾
			$extraData = LocalLegacyManager::getExtraDataByStore($brand, $allowOpCenterIds, $stDate, $endDate, $storeKeys);
			
			#因無areaId, 故只能從門店過濾
			$validStoreKeys = collect($params->storeList)->pluck('storeKey')->values()->all();
			
			$extraData = collect($extraData)->filter(function($item, $key) use($validStoreKeys) {
				
				return in_array($item['storeKey'], $validStoreKeys);
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
	 * @params: collection
	 * @return: array
	 */
	private function _buildBaseData($params)
	{
		#整合追加資料(order有storeNo, extra有storeKey)
		$baseData = collect($params->orderData)->merge($params->extraData);
		
		#只取storeList有對應的資料(因各種來源可判別條件不同, 故再過濾)
		$authStoreKeys = collect($params->storeList)->pluck('storeKey')->unique()->all();
		
		#處理包裝轉換及統一輸出資料格式
		$baseData = collect($baseData)->map(function($item, $key){
			
			$temp['expectedDate']	= $item['expectedDate'];
			$temp['storeKey'] 		= StoreManager::buildStoreKey($item['storeNo']);
			$temp['shortCode']		= $item['shortCode'];
			$temp['productName']	= $item['productName'];
			$temp['qty'] 			= round(intval($item['qty']) * PurchaseManager::getPackagingScale($item['shortCode']), 2);
			$temp['amount'] 		= round($item['amount'], 2);
			
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
			#1.計算查詢範圍日期
			$this->_buildDateList($params);
			
			#2.Build productList
			#$this->_buildProductList($params);
		
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
	private function _buildDateList($params)
	{
		$st		= Carbon::parse($params->stDate);
		$end	= Carbon::parse($params->endDate);
		$period = CarbonPeriod::create($st, '1 day', $end);
		
		$dateList = [];
		
		foreach ($period as $date) 
		{
			$dateList[] = $date->format('Y-m-d');
		}
		
		$params->dateList = $dateList;
	}
	
	/* Get order data
	 * @params: array
	 * @return: array
	 */
	private function _buildProductList($params)
	{
		#列出查詢日期內所有product(可能不需要)
		$baseData = $params->baseData;
		
		$productList = collect($baseData)->unique('shortCode')->mapWithKeys(function($item, $key){
			return [$item['shortCode'] => $item['productName']];
		})->sortKeys()->all();
		
		$params->productList = $productList;
	}
	
	/* 依Store
	 * @params: array
	 * @return: array
	 */
	private function _parsingByStore($params)
	{
		$baseData = $params->baseData;
		
		if (empty($baseData))
		{
			$params->data = [];
			return;
		}
		
		$dateList	= $params->dateList;
		$orderData 	= collect($baseData)->groupBy('storeKey');
		
		#用store mapping data
		$result = collect($params->storeList)->map(function($item, $key) use($orderData, $dateList) {
			
			$data = data_get($orderData, $item['storeKey'], []);
			
			$temp['areaName']	= $item['areaName'];
			$temp['storeKey'] 	= $item['storeKey'];
			$temp['storeName'] 	= $item['storeName'];
			
			#格式化成可直接輸出模式
			$detail = collect($data)->sortBy('shortCode')->groupBy('productName')->map(function($items, $key) {
				
				return $items->groupBy('expectedDate')->map(function($items, $key) {
						$temp['qty'] 	= round($items->pluck('qty')->sum(), 2);
						#$temp['amount'] = round($items->pluck('amount')->sum(), 2);
						return $temp['qty'];
					})->all();
			})->all();
			
			#只計算總數量By day
			$temp['total'] = collect($dateList)->mapWithKeys(function($date, $key) use($detail){
				
				$qty = collect($detail)->pluck($date)->sum();
				return [$date => $qty];
			})->all();
			
			$temp['detail'] = $detail;
			
			return $temp;
		})->toArray();
		
		$params->data = $result;
	}
	
	/* Export data(目前無下載)
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
		$outputHeader = array_merge(['區域', '門店代號', '門店名稱'], $sourceData['dateList']);
		
		#每個product要一個sheet
		/* foreach($sourceData['data'] as $shortCode => $item)
		{
			$storeData = data_get($sourceData['data'], $shortCode, []);
			$productName = "{$shortCode}_{$item['productName']}";
			
			if (empty($storeData))
				continue;
			
			$export[$productName] = [];
			$export[$productName][] = $outputHeader;
			
			#使用header來控制顯示順序,先TP後KH
			foreach($sourceData['storeList'] as $index => $store)
			{
				$row = [];
				$row[] = $store['areaName'];
				$row[] = $store['posId'];
				$row[] = $store['storeNo'];
				$row[] = $store['storeName'];
				
				$storeKey = $store['storeKey'];
				
				#要按Header的順序
				foreach($sourceData['dateList'] as $date)
				{
					$row[] = data_get($storeData, "{$storeKey}.{$date}.qty", 0);
				}
				
				$export[$productName][] = $row;
			}
		}
		 */
		return $export;
	}
}
