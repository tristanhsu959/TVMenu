<?php

namespace App\Services\PurchaseReport;

use App\Facades\AppManager;
use App\Facades\PurchaseManager;
use App\Facades\StoreManager;
use App\Facades\LocalLegacyManager;
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
class PerformanceService
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
			'areaIds'		=> [],
			'report'		=> [],
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
		$this->_statistics['brandId']		= $params->brand->value;
		$this->_statistics['brandCode']		= $params->brand->code();
		$this->_statistics['searchBrandId']	= $params->whereBrandId;
		$this->_statistics['startDate'] 	= $params->stDate;
		$this->_statistics['endDate']		= $params->endDate;
		$this->_statistics['areaIds']		= $params->areaIds;
		$this->_statistics['report']		= $params->report;
		$this->_statistics['hasResult']		= FALSE;
		
		#無值不cache
		#因有補全門店,故基本上不會有空值,故hasResult應該都會是TRUE
		if (! empty(Arr::flatten($this->_statistics['report'])))
		{
			$this->_statistics['hasResult']		= TRUE;
			$this->_statistics['exportName']	= "營運概況";
			$this->_statistics['exportToken'] 	= bin2hex($params->cacheKey); #hex2bin
			Cache::put($params->cacheKey, $this->_statistics, now()->addMinutes(15));
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
			#營運概況取固定的product
			$productConfigs		= config('web.purchase.report.performance');
			#有粗細麵自動加入南區的short code
			$noodleShortCodes 	= config('web.purchase.product_type.noodles.queryShortCodes'); 
			
			#八方/蘿蔔的項目似乎不同
			$codeGroup = data_get($productConfigs, $params->whereBrandId, []);
			
			#取出所有的short code
			$codes = collect($codeGroup)->collapse()->pluck('code')->map(function($item, $key) use($noodleShortCodes){
				
				$includes = data_get($noodleShortCodes, $item, NULL);
				
				if (empty($includes))
					return $item;
				else
					return $includes;
			})->flatten()->toArray();
			
			$ids = PurchaseManager::getProductIdByShortCode($params->brand, $params->allowOpCenterIds, $codes);
			
			if (empty($ids))
				throw new Exception('查無參照的產品');
			
			$params->productGroup	= $codeGroup;
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
		
		if ($params->whereBrandId == Brand::BAFANG->value)
			$storeList = StoreManager::getStoreList($brand, $allowOpCenterIds, $allowAreaIds, $stDate, $endDate);
		else if ($params->whereBrandId == Brand::LUOBO->value)
			$storeList = StoreManager::getLbStoreList($brand, $allowOpCenterIds, $allowAreaIds, $stDate, $endDate);
		else
			$storeList = [];
		
		#這裏不排除工廠學區店
		#$params->storeList = StoreManager::filterFactoryStore($brand, $storeList);
		
		$storeList = collect($storeList)->map(function($item, $key){
			#報表另產生舊store no格式欄位
			$item['oldStoreNo'] = Str::of($item['storeNo'])->replaceMatches('/^(TP|KH|TS|RL)/', '')->toString();
			return $item;
		})->toArray();
		
		$params->storeList = $storeList;
	}
	/* ====================== 主流程 End ====================== */
	
	/* Get order data
	 * @params: array
	 * @return: array
	 */
	private function _getDataFromDB($params)
	{
		/*[
			"expectedDate" => "2026-06-17"
			"qty" => "1"
			"amount" => "45.000000"
			"storeNo" => "KH1100000"
			"shortCode" => "0202"
		]
		*/
	
		try
		{
			#目前只有八方有此報表
			#因八方及蘿蔔要分開,故brand改代入search brand
			$brand 				= $params->brand;
			$searchBrand		= Brand::tryfrom($params->whereBrandId);
			$stDate				= Carbon::parse($params->stDate)->format('Y-m-d H:i:s');
			$endDate 			= Carbon::parse($params->endDate)->addDay()->format('Y-m-d H:i:s');
			$productIds 		= $params->productIds;
			$allowOpCenterIds 	= $params->allowOpCenterIds;
			$allowAreaIds 		= $params->allowAreaIds;
			
			$orderData = $this->_repository->getOrderDataByPerformance($brand, $allowOpCenterIds, $allowAreaIds, $stDate, $endDate, $productIds, $searchBrand);
			
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
		/*0 => array:8 [▼
			"expectedDate" => "2026-06-01"
			"storeNo" => "22000321"
			"shortCode" => "0101"
			"productName" => "水餃皮"
			"factoryNo" => "TW_TP"
			"factoryName" => "淡水總廠"
			"qty" => "1"
			"amount" => "27"
		]*/
		
		try
		{
			$brand 				= $params->brand;
			$stDate				= Carbon::parse($params->stDate)->format('Y-m-d H:i:s');
			$endDate 			= Carbon::parse($params->endDate)->addDay()->format('Y-m-d H:i:s');
			$productCodes 		= $params->productCodes; #舊系統用product code
			$allowOpCenterIds 	= $params->allowOpCenterIds;
			$allowAreaIds 		= $params->allowAreaIds;
			
			$extraData = LocalLegacyManager::getExtraDataByProduct($brand, $allowOpCenterIds, $stDate, $endDate, $productCodes);
			
			#因無areaId, 故只能從門店過濾
			#20260818:一樣用storeKey過濾Area權限不影響
			$validStoreKeys = collect($params->storeList)->pluck('storeKey')->values()->all();
			
			$extraData = collect($extraData)->map(function($item, $key){
				$item['storeKey'] = Str::take($item['storeNo'], 7);
				return $item;
			})->filter(function($item, $key) use($validStoreKeys) {
				return in_array($item['storeKey'], $validStoreKeys);
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
		/*[ 
			"expectedDate" => "2026-07-09"
			"qty" => "25"
			"amount" => "1750.000000"
			"storeNo" => "TP10000011"
			"shortCode" => "0001"
		]
		*/
		$convert = config('web.purchase.product_type.noodles.convert');
		
		#整合追加資料,Group Data但先不計算
		$orderData = collect($orderData)->merge($extraData);
		
		$baseData = collect($orderData)->map(function($item, $key) use($convert) {
			$item['storeKey'] 	= StoreManager::buildStoreKey($item['storeNo']);
			$item['qty'] 		= round(intval($item['qty']) * PurchaseManager::getPackagingScale($item['shortCode']), 2);
			
			$convertShortCode	= data_get($convert, $item['shortCode'], NULL);
			#有值才轉換, 只是為了避免重複
			if (! empty($convertShortCode))
				$item['shortCode'] = $convertShortCode;
			
			return $item;
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
			#1.Build header data
			$this->_buildHeader($params);
			
			#2.By area & store
			$this->_parsingByStore($params);
			
			#3.Format output
			$this->_generateOutput($params);
			
			return $params;
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('解析報表資料發生錯誤');
		}
	}
	
	/* Header
	 * @params: 
	 * @return: array
	 */
	private function _buildHeader($params)
	{
		$groupList = collect($params->productGroup)->map(function($group, $key){
			
			return collect($group)->mapWithKeys(function($item, $key){
				return [$item['code'] => $item['name']];
			})->toArray();
			
		})->toArray();
		
		#Build header
		$header = collect(['序號', '行政區', '店名', '門店代碼', '開店日期'])
			->merge(array_values($groupList['filling']))
			->merge(['餡料總和', '餡料平均', '餡料銷售金額'])
			->merge(array_values($groupList['wrapper']))
			->merge(['皮總和', '皮銷售金額', '餡皮比率'])
			->when($params->whereBrandId == Brand::BAFANG->value, function($collection, $value) use($groupList){
				$collection = $collection->merge(array_values(data_get($groupList, 'drink', [])))
							->merge(['飲品總和', '飲品銷售金額', '銷售總額', '銷售日均額', '營業天數']);
				return $collection;
			})
			->when($params->whereBrandId == Brand::LUOBO->value, function($collection, $value) use($groupList){
				$collection = $collection->merge(array_values(data_get($groupList, 'noodle', [])))
							->merge(['麵球總和', '麵球銷售金額', '銷售總額', '銷售日均額', '營業天數']);
				return $collection;
			})->all();
		
		$params->set('report.header', $header);
	}
	
	/* 依區->門店->產品
	 * @params: array
	 * @return: array
	 */
	private function _parsingByStore($params)
	{
		#依區->門店->產品
		$baseData = $params->baseData;
		
		#改用門店來取, 排序比較不會亂
		$result = collect($params->storeList)->map(function($item, $key) use($baseData){
			
			$data = data_get($baseData, $item['storeKey'], []);
			
			$temp['district'] 	= $item['district'];
			$temp['storeKey'] 	= $item['storeKey'];
			$temp['storeNo'] 	= $item['storeNo'];
			$temp['oldStoreNo'] = $item['oldStoreNo'];
			$temp['storeName'] 	= $item['storeName'];
			$temp['areaId'] 	= $item['areaId'];
			$temp['areaName'] 	= $item['areaName'];
			$temp['openDate'] 	= $item['openDate'];
			
			if (empty($data))
			{
				$temp['products'] = [];
				$temp['openDays'] = 0;
			}
			else
			{	
				$temp['products'] = collect($data)->groupBy('shortCode')->map(function($items, $key){
				
					#DB其實是unique的(每個品項只有一筆), 但還是當成array來處理
					$product['qty'] 	= round(floatval($items->pluck('qty')->sum()), 2); #因有乘係數, 故要用float
					$product['amount'] 	= round(floatval($items->pluck('amount')->sum()), 2); #因有乘係數, 故要用float
					
					return $product;
				})->toArray();
				
				$temp['openDays'] = collect($data)->whereNotNull('expectedDate')->pluck('expectedDate')->unique()->count();
			}	
			
			return $temp;
			
		})->toArray();
		
		$params->orders = $result;
	}
	
	/* 改成產出row data(降低JS render效能, 匯出可直接使用)
	 * @params: array
	 * @return: array
	 */
	private function _generateOutput($params)
	{
		#Sheets by area
		if (empty($params->orders))
			return [];
		
		$searchBrandId = $params->whereBrandId;
		$orders = collect($params->orders)->groupBy('areaId');
		
		#須依據header順序
		foreach($orders as $areaId => $areaGroup)
		{
			$areaName = $areaGroup->pluck('areaName')->first();
			$areaData[$areaName] = [];
			$sn = 1;
			
			foreach($areaGroup as $storeData)
			{
				$opendays 	= data_get($storeData, 'openDays'); #營業天數
				
				#Build product => qty,amount mapping : 確保每個product都要對應值
				$total = $this->_getProductOutput($searchBrandId, $storeData);
				
				$row = [];
				$row['sn']	= $sn;
				$row['district'] 	= data_get($storeData, 'district');
				$row['storeName'] 	= data_get($storeData, 'storeName');
				#$row['storeKey'] 	= data_get($storeData, 'storeKey');
				$row['oldStoreNo'] 	= data_get($storeData, 'oldStoreNo');
				$row['openDate']	= data_get($storeData, 'openDate');
				
				#各餡量
				foreach($total['filling'] as $code => $value) 
				{
					$row[$code] = $value['qty'];
				}
				
				$fillingQty 	= collect($total['filling'])->pluck('qty')->sum();
				$fillingAmount 	= collect($total['filling'])->pluck('amount')->sum();
				
				$row['fillingQty'] 		= $fillingQty; #餡料總和
				$row['fillingAvg'] 		= empty($opendays) ? 0 : round($fillingQty / $opendays, 2); #餡料平均
				$row['fillingAmount'] 	= $fillingAmount; 	#餡料銷售金額
				
				$wrapperQty 	= collect($total['wrapper'])->pluck('qty')->sum();
				$wrapperAmount 	= collect($total['wrapper'])->pluck('amount')->sum();
				
				#各皮量
				foreach($total['wrapper'] as $code => $value) 
				{
					$row[$code] = $value['qty'];
				}
				
				$row['wrapperQty'] 		= $wrapperQty; 	#皮總和
				$row['wrapperAmount'] 	= $wrapperAmount; #皮銷售金額
				$row['wrapperRatio'] 	= empty($fillingQty) ? 0 : round($wrapperQty / $fillingQty, 2); #餡皮比率 (皮/餡)
				
				if ($searchBrandId == Brand::BAFANG->value)
				{
					$drinkQty 		= collect($total['drink'])->pluck('qty')->sum();
					$drinkAmount 	= collect($total['drink'])->pluck('amount')->sum();
				
					#各飲料量
					foreach($total['drink'] as $code => $value) 
					{
						$row[$code] = $value['qty'];
					}
					
					$row['drinkQty'] 	= $drinkQty; #飲料總和
					$row['drinkAmount'] = $drinkAmount; #飲料總額
					
					#銷售總額
					$row['totalAmount'] = $fillingAmount + $wrapperAmount + $drinkAmount; 
				}
				
				if ($searchBrandId == Brand::LUOBO->value)
				{
					$noodleQty 		= collect($total['noodle'])->pluck('qty')->sum();
					$noodleAmount 	= collect($total['noodle'])->pluck('amount')->sum();
				
					#各飲料量
					foreach($total['noodle'] as $code => $value) 
					{
						$row[$code] = $value['qty'];
					}
					
					$row['noodleQty'] 	= $noodleQty; #麵球總和
					$row['noodleAmount']= $noodleAmount; #麵球銷售金額
					
					#銷售總額
					$row['totalAmount'] = $fillingAmount + $wrapperAmount + $noodleAmount; 
				}
				
				$row['avgAmount'] 	= empty($opendays) ? 0 : round($row['totalAmount'] / $opendays, 2);; #日均額
				$row['openDays'] 	= $opendays; #營業天數
				$sn++;
				
				$areaData[$areaName][] = $row;
			}
		}
		
		$params->set('report.sheets', array_keys($areaData));
		$params->set('report.amountFields', ['fillingAmount', 'wrapperAmount', 'drinkAmount', 'noodleAmount', 'totalAmount']);
		$params->set('report.data', $areaData);
	}
	
	/* 依據config product codes 先處理資料
	 * @params: array
	 * @return: array
	 */
	private function _getProductOutput($searchBrandId, $storeData)
	{
		$configMap = $this->_getConfigProductMap($searchBrandId);
		$result = [];
		
		foreach($configMap as $group => $items)
		{
			$result[$group] = [];
			$row = [];
			
			foreach($items as $code => $name)
			{
				$row[$code] = [];
				$row[$code]['qty'] 		= intval(data_get($storeData, "products.{$code}.qty", 0));
				$row[$code]['amount'] 	= floatval(data_get($storeData, "products.{$code}.amount", 0));
			}

			$result[$group] = $row;
		}
		
		return $result;
	}
	
	/* 依據config product codes 先處理資料
	 * @params: array
	 * @return: array
	 */
	private function _getConfigProductMap($searchBrandId)
	{
		$config = config("web.purchase.report.performance.{$searchBrandId}");
		
		$map = collect($config)->map(function($groups, $key){
			return collect($groups)->mapWithKeys(function($item, $key){
				return [$item['code'] => $item['name']];
			})->all();;
		})->all();
		
		return $map;
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
			$export = $this->_buildExportData($sourceData['report']);
			
			#Write export to file
			$brandName = Brand::tryFrom($sourceData['searchBrandId'])->label();
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
	private function _buildExportData($reportData)
	{
		$data 			= $reportData['data'];
		$sheets 		= $reportData['sheets'];
		$header 		= $reportData['header'];
		$amountFields 	= $reportData['amountFields'];
		
		#每個product要一個sheet
		foreach($sheets as $key => $sheetName)
		{
			$export[$sheetName] = [];
			$export[$sheetName][] = $header;
			
			$storeData = data_get($data, $sheetName, []);
			
			if (empty($storeData))
				continue;
			
			foreach($storeData as $key => $rowData)
			{
				#dollar format
				$rowData = collect($rowData)->map(function($value, $key) use($amountFields){
					
					if (in_array($key, $amountFields))
						$value = Number::currency($value, precision: 0);
					
					return $value;
				})->values()->all();
				
				$export[$sheetName][] = $rowData;
			}
		}
		
		return $export;
	}
}