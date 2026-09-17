<?php

namespace App\Services\PurchaseNotOrder;

use App\Facades\AppManager;
use App\Facades\PurchaseManager;
use App\Facades\StoreManager;
use App\Facades\LocalLegacyManager; #from local
use App\Repositories\PurchaseNotOrderRepository;
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
class ProductService
{
	private $_statistics	= [];
   
	public function __construct(protected PurchaseNotOrderRepository $_repository)
	{
		$this->_statistics = [
			'type'		=> '',
			'calc'		=> '',
			'by'		=> '',
			'brandId'		=> '', #export
			'brandCode'		=> '', 
			'startDate'		=> '', #Y-m-d
            'endDate'   	=> '',
			'productList'	=> [],
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
		$this->_statistics['calc']			= $params->calc; 
		$this->_statistics['by']			= $params->by; 
		$this->_statistics['brandId']		= $params->brand->value; 
		$this->_statistics['brandCode']		= $params->brand->code(); 
		$this->_statistics['startDate'] 	= $params->stDate; 
		$this->_statistics['endDate'] 		= $params->endDate;
		$this->_statistics['productList'] 	= $params->productList;
		$this->_statistics['data'] 			= $params->data;
		$this->_statistics['hasResult'] 	= FALSE;
		
		#無值不cache
		if (! empty($params->data))
		{
			$this->_statistics['hasResult'] 	= TRUE;
			$this->_statistics['exportToken'] 	= bin2hex($params->cacheKey); #hex2bin
			$this->_statistics['exportName'] 	= '未訂貨查詢';

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
			
			$orderData = $this->_getDataFromDB($params);
			
			$extraData = $this->_getExtraDataFromDB($params); #追加目前在舊系統,要另外處理
			
			$this->_buildBaseData($params, array_filter($orderData) , array_filter($extraData));
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
	
	/* Get order data
	 * @params: 
	 * @return: array
	 */
	private function _getDataFromDB($params)
	{
		/*0 => array:12 [
			"expectedDate" => "2026-05-29"
			"area" => "中彰投-八方"
			"storeNo" => "KH4010002"
			"qty" => "90"
			"productName" => "招牌餡"
			"shortCode" => "0001"
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
			#要有ErpNo,用來判別是否為有效產品
			$orderData = $this->_repository->getOrderDataByProductId($brand, $allowOpCenterIds, $allowAreaIds, $stDate, $endDate, $productIds);
			
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
			$productCodes 		= $params->shortCodes;
			$allowOpCenterIds 	= $params->allowOpCenterIds;
			$allowAreaIds 		= $params->allowAreaIds;
			
			#維持原狀,不判別opCenter, 最後由門店一起過濾
			$extraData = LocalLegacyManager::getExtraDataByProduct($brand, $allowOpCenterIds, $stDate, $endDate, $productCodes);
			
			#因無areaId, 故只能從門店過濾
			$validStoreKeys = collect($params->storeList)->pluck('storeKey')->values()->all();
			
			$extraData = collect($extraData)->filter(function($item, $key) use($validStoreKeys) {
				$storeKey = Str::take($item['storeNo'], 7);
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
	private function _buildBaseData($params, $orderData, $extraData = [])
	{
		#整合追加資料
		$baseData = collect($orderData)->merge($extraData);
		
		$authStoreKeys = collect($params->storeList)->pluck('storeKey')->unique()->all();
		
		#不處理包裝轉換,只算數量
		$baseData = collect($baseData)->map(function($item, $key){
			
			$item['storeKey'] 	= StoreManager::buildStoreKey($item['storeNo']);
			return $item;
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
			#1.Build productList
			$this->_buildProductList($params);
		
			#2.Data header
			$this->_buildHeader($params);
			
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
	
	/* Get order data
	 * @params: array
	 * @return: array
	 */
	private function _buildProductList($params)
	{
		#必須從查詢的productId來取list, 不能從Order,因項目會少
		#因查Name時, 只有Id沒有shortCode
		$result = PurchaseManager::getProductShortCodeById($params->brand, $params->allowOpCenterIds, $params->productIds);
		
		$params->productList = $result;
	}
	
	/* 計算日期天數
	 * @params: 
	 * @return: array
	 */
	private function _buildHeader($params)
	{
		$productList = $params->productList;
		$header = [
			'areaName'	=> '區域',
			'posId'		=> 'POS店號',
			'storeKey' 	=> '門店代碼',
			'storeName'	=> '門店名稱'
		];
		
		$header = collect($header)->concat($productList)->values()->all();
		$params->set('data.header', $header);
	}
	
	
	/* 依Store
	 * @params: array
	 * @return: array
	 */
	private function _parsingByStore($params)
	{
		$orderData = $params->baseData;
		
		if (empty($orderData))
		{
			$params->data = [];
			return;
		}
		
		#先整理資料以便Mapping,只有留qty對應即可
		$productQty = collect($orderData)->groupBy('storeKey')->map(function($items, $key){
			
			#理論上每個產只有會一筆product資料,但現實上仍有可能有兩筆以上可能性
			return $items->groupBy('shortCode')->mapWithKeys(function($items, $key){
				$qty = $items->pluck('qty')->sum();
				$shortCode = $items->pluck('shortCode')->first();
				
				return [$shortCode => $qty];
			})->all();
		})->all();
		
		#以門店取值,以便過濾無效門店
		$calc = $params->calc;
		$queryProductCount = count($params->productList); #必須用全有product比對
		
		$result = collect($params->storeList)->map(function($item, $key) use($calc, $productQty, $queryProductCount) {
			
			$storeProductQty = data_get($productQty, $item['storeKey'], []);
			
			$temp['storeKey'] 	= $item['storeKey'];
			$temp['storeName'] 	= $item['storeName'];
			$temp['areaId'] 	= $item['areaId'];
			$temp['areaName'] 	= $item['areaName'];
			$temp['posId'] 		= $item['posId'];
			$temp['productQty'] = $storeProductQty;
			
			$notOrderItems = collect($storeProductQty)->filter(function($qty, $key) {
				return $qty <= 0;
			})->count();
			
			if (empty($storeProductQty))
				$temp['isNotOrder'] = TRUE;
			else
			{
				if ($calc == 'whereall')
					$temp['isNotOrder'] = ($notOrderItems == $queryProductCount);
				else #whereany or 無法判別時都套用此規則
					$temp['isNotOrder'] = ($notOrderItems <= $queryProductCount && $notOrderItems > 0);
			}
			
			return $temp;
		})->filter(function($item, $key){
			#過濾出無訂單門店
			return $item['isNotOrder'];
		})->all();
		
		$params->set('data.store', $result);
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
			$fileName = Str::replaceArray('?', [$brandName, $sourceData['exportName'], $sourceData['startDate']], '?_?_?.xlsx');
			$filePath = Storage::disk('export')->path($fileName);
			
			$writer = new Writer();
			$writer->openToFile($filePath);
			
			$centerStyle = (new Style())->setCellAlignment(CellAlignment::CENTER);
			
			$sheet = $writer->getCurrentSheet();
			$sheet->setName('未訂貨門市');
				
			foreach($export as $data)
			{
				$row =  Row::fromValues($data, $centerStyle);
				$writer->addRow($row);
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
		$export[] = data_get($sourceData, 'data.header', '');
		
		$productList	= data_get($sourceData, 'productList', []);
		$storeData 		= data_get($sourceData, 'data.store', []);
		
		foreach($storeData as $item)
		{
			$row = [];
			
			$row[] = $item['areaName'];
			$row[] = $item['posId'];
			$row[] = $item['storeKey'];
			$row[] = $item['storeName'];
			
			foreach($productList as $shortCode => $name)
			{
				$row[] = data_get($item, "productQty.{$shortCode}", 0);
			}
			
			$export[] = $row;
		}
		
		return $export;
	}
}
