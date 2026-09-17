<?php

namespace App\Manager;

use App\Facades\AppManager;
use App\Manager\Repositories\PurchaseRepository;
use App\Libraries\Purchase\AreaLib;
use App\Enums\OpCenter;
use App\Enums\Brand;
use App\Enums\Area;
use App\Enums\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

/* New Order sys Common */
class PurchaseManager
{
	public function __construct(protected PurchaseRepository $_repository)
	{
	}
	
	/* 
	 * @params: 
	 * @return: boolean
	 */
	public function getAllOpCenters()
	{
		return OpCenter::getAll();
	}
	
	/* 
	 * @params: 
	 * @return: boolean
	 */
	public function getAllowOpCenters($brand, $filterOpCenters = [])
	{
		$currentUser = AppManager::getCurrentUser();
		$authOpCenters = $currentUser->getOpCenterPermissions();
		
		#芳珍無訂貨功能
		if ($brand == Brand::BAFANG)
		{
			if (empty($filterOpCenters))
				return $authOpCenters;
			else
				return $filterOpCenters;
		}
		else if ($brand == Brand::BUYGOOD)
			return $this->getAllOpCenters();
		else 
			return [];
	}
	
	/* 
	 * @params: 
	 * @return: boolean
	 */
	public function getAllAreas($filterAreas = [])
	{
		if (empty($filterAreas))
			return Area::getAll();
		else
			return $filterAreas;
	}
	/* 
	 * @params: 
	 * @return: boolean
	 */
	public function getAllowAreas($filterAreas = [])
	{
		$currentUser = AppManager::getCurrentUser();
		$authAreas = $currentUser->getPurchaseAreaPermissions();
		
		if (empty($filterAreas))
			return $authAreas;
		else
			return array_map('intval', $filterAreas);
	}
	
	/* 
	 * @params: 
	 * @return: boolean
	 */
	public function getAllowSalesAreas($filterAreas = [])
	{
		$currentUser = $this->getCurrentUser();
		$authAreas = $currentUser->getSalesAreaPermissions();
		
		if (empty($filterAreas))
			return $authAreas;
		else
			return array_map('intval', $filterAreas);
	}
	
	/* 取對應nOrder的設定值
	 * @params: enum
	 * @return: array
	 */
	/* public function getOpCenterNo($brand, $authOpCenter = [])
	{
		#只有八方中彰投營運中心有影響,御廚都只有台北
		#有設定則使用設定值,不然預設是全部營運中心
		$defaultOpCenter = OpCenter::toValueArray();
		
		if ($brand == Brand::BAFANG && ! empty($authOpCenter))
			return $authOpCenter;
		
		return $defaultOpCenter;
	} */
	
	/* 取對應nOrder的設定值
	 * @params: enum
	 * @return: array
	 */
	/* public function getBrandShortCode($brand)
	{
		#只有八方蘿蔔
		if ($brand == Brand::BAFANG)
			return [Brand::BAFANG->shortCode(), Brand::LUOBO->shortCode()];
		else
			return [$brand->shortCode()];
	} */
	
	
	/******************** Factory ********************/
	/* Factory No
	 * @params: enum
	 * @return: array
	 */
	public function getFactoryNo($brand)
	{
		#台北/高雄(預留,可不用判別工廠)
		if ($brand == Brand::BAFANG)
			return [Factory::TP->value, Factory::KH->value];
		else if ($brand == Brand::BUYGOOD)
			return [Factory::TS->value, Factory::RL->value];
		else if ($brand == Brand::FJVEGGIE)
			return [Factory::TP->value, Factory::KH->value]; #同八方
		else 
			return [];
	}
	
	/* 取工廠清單
	 * @params: int
	 * @return: array
	 */
	public function getFactoryList($brand, $opCenter, $returnMapping = TRUE)
	{
		$factoryNos = $this->getFactoryNo($brand);
		$factory = $this->_repository->getFactoryList($opCenter, $factoryNos);
		
		#To key-value
		if ($returnMapping === TRUE)
		{
			$factory = collect($factory)->mapWithKeys(function($item, $key){
				return [$item['factoryNo'] => $item['factoryName']];
			})->toArray();
		}
			
		return $factory;
	}
	
	/******************** Product ********************/
	/* 取Name對應的ProductId(查詢時)
	 * @params: int
	 * @params: boolean
	 * @return: array
	 */
	public function getProductIdByName($brand, $opCenter, $name)
	{
		if (empty($name))
			return [];
		
		$brandId = $brand->value;
		
		$result = $this->_repository->getProductIdByName($brandId, $opCenter, $name);
		
		#format to int
		$ids = collect($result)->map(function($item, $key){
			return (int)$item;
		})->toArray();
		
		return $ids;
	}
	
	/* 取ShortCode對應的ProductId(查詢時)
	 * @params: int
	 * @params: boolean
	 * @return: array
	 */
	public function getProductIdByShortCode($brand, $opCenter, $shortCodes)
	{
		if (empty($shortCodes))
			return [];
		
		$brandId = $brand->value;
		
		$result = $this->_repository->getProductIdByShortCode($brandId, $opCenter, $shortCodes);
		
		#format to int
		$ids = collect($result)->map(function($item, $key){
			return (int)$item;
		})->toArray();
		
		return $ids;
	}
	
	/* 取ShortCode對應的ProductId(查詢時)
	 * @params: int
	 * @params: boolean
	 * @return: array
	 */
	public function getProductShortCodeById($brand, $opCenter, $productIds)
	{
		#只能用id取product情況下使用
		$brandId = $brand->value;
		
		$result = $this->_repository->getProductShortCodeById($brandId, $opCenter, $productIds);
		
		#format to int
		$mappings = collect($result)->mapWithKeys(function($item, $key){
			return [$item['shortCode'] => $item['productName']];
		})->sortKeys()->toArray();
		
		return $mappings;
	}
	
	/* 取對應的Product&Short code mapping
	 * @params: int
	 * @params: boolean
	 * @return: array
	 */
	public function getProductShortCodeMapping($brand, $allOpCenter = [])
	{
		$productData = $this->_repository->getProductAndShortCode($brand, $allOpCenter);
		
		#因不分工廠, 現狀會有重複, 要再濾除
		$productMapping = collect($productData)->unique('productNo')->all();
		
		/* $productMapping = $productData->mapWithKeys(function($item, $key){
			return [$item['productNo'] => $item['productName']];
		})->all(); */
		
		#改為直接回傳, 不轉換成key-value
		return $productMapping;
	}
	
	/* 取對應的Group設定值(Dashboard自訂)
	 * @params: string
	 * @return: array
	 */
	public function getGroupByShortCode($brandId, $code)
	{
		$groupConfig = config("web.purchase.product_type.groupPrefix.{$brandId}");
		
		foreach($groupConfig as $config)
		{
			if (Str::startsWith($code, $config['pattern']))
			{
				$group['groupId'] 	= $config['id'];
				$group['groupName'] = $config['name'];
				
				return $group;
			}
		}
		
		return ['groupId' => '', 'groupName' => ''];
	}
	
	/* 取對應的Group設定值
	 * @params: string
	 * @return: array
	 */
	public function getPackagingScale($code)
	{
		$config = config('web.purchase.product_type.packagingScale');
		
		return data_get($config, $code, 1);
	}
	
	/* 取出貨產品設定, 有啟用的產品清單 - purchase product setting(後台設定)
	 * @params: int
	 * @return: string
	 */
	public function getEnableProductSettingsAndCategory($brand, $allowOpCenter)
	{
		#同總量邏輯
		$enableProducts 	= $this->_repository->getEnableProductSettings($brand);
		$enableShortCodes 	= collect($enableProducts)->pluck('shortCode')->values()->all();
		
		#再至訂貨取到對應的產品名(這裏會分營運中心)
		#shortCode => productName
		$productMapping = $this->getProductShortCodeMapping($brand, $allowOpCenter);
		
		#Build options
		/*array:4 [
			"shortCode" => "0001"
			"productName" => "招牌餡"
			"groupId" => 1
			"groupName" => "餡類"
		]
		*/
		
		$brandId = $brand->value;
		
		#因設定沒分營運中心, 故要改以訂貨的為依據過濾
		$list = collect($productMapping)->filter(function($item, $key) use($enableShortCodes){
			return in_array($item['productNo'], $enableShortCodes);
		})->map(function($item, $key) use($brandId) {
			
			$temp['shortCode'] 	= $item['productNo'];
			$temp['productName']= $item['productName'];
			
			$category = $this->getGroupByShortCode($brandId, $temp['shortCode']);
			
			$temp['groupId']	= $category['groupId'];
			$temp['groupName'] 	= $category['groupName'];
			
			return $temp;
		})->values()->all();
		
		#要分成category & product對應
		$category = collect($list)->groupBy('groupId')->map(function($items, $key){
			$temp['catId'] 	= $items->pluck('groupId')->unique()->first();
			$temp['catName']= $items->pluck('groupName')->unique()->first();
			
			return $temp;
		})->mapWithKeys(function($item, $key){
			return [$item['catId'] => $item['catName']];
		})->toArray();
		
		#Build product
		$products = collect($list)->groupBy('groupId')->map(function($items, $key){
			return $items->map(function($item, $key){
				unset($item['groupId']);
				unset($item['groupName']);
				
				return $item;
			});
			
			return $items;
		})->toArray();
		
		return [$category, $products];
	}
	
	
	/******************** 供應商產品 ********************/
	/* 供應商產品-不分品牌
	 * @params: int
	 * @return: string
	 */
	public function getSupplierProductWithCategory()
	{
		#沒有重複問題,可以直接取Id使用
		#不分品牌, 由門店來過濾數據即可
		$enableProducts = $this->_repository->getSupplierProductList();
		
		#整理成分類模式
		$groups = collect($enableProducts)->groupBy('supplierId');
		
		#供應商category
		$category = $groups->mapWithKeys(function($items, $key){
			$name = $items->pluck('supplierName')->first();
			return [$key => $name];
		})->all();
		
		#Product Mapping
		$productGroup = $groups->map(function($items, $key){
			
			return $items->mapWithKeys(function($item, $key){
				$id 	= $item['productId'];
				$name	= "{$item['shortCode']} {$item['productName']}";
				
				return [$id => $name];
			})->all();
		})->all();
		
		return [$category, $productGroup];
	}
	
	/* 取Name對應的ProductId(查詢時)
	 * @params: int
	 * @params: boolean
	 * @return: array
	 */
	public function getSupplierProductIdByName($name)
	{
		if (empty($name))
			return [];
		
		$result = $this->_repository->getSupplierProductIdByName($name);
		
		#format to int
		$ids = collect($result)->map(function($item, $key){
			return (int)$item['productId'];
		})->toArray();
		
		return $ids;
	}
	
	/* 供應商產品-不分品牌
	 * @params: int
	 * @return: string
	 */
	public function getSupplierProductListByIds($ids)
	{
		#依Id取清單
		$enableProducts = $this->_repository->getSupplierProductList();
		
		$productList = collect($enableProducts)->filter(function($item, $key) use($ids){
			return in_array($item['productId'], $ids);
		})->mapWithKeys(function($item, $key){
			return [$item['productId'] => $item['productName']];
		})->all();
		
		return $productList;
	}
	/******************** Store ********************/
	/* Get store data by brand
	 * @params: int
	 * @params: array
	 * @return: array
	 */
	/* public function getStoreList($brand, $opCenter, $userAreaIds, $stDate = NULL, $endDate = NULL)
	{
		/*0 => array:9 [▼
			"areaId" => 1
			"storeNo" => "TP10600172"
			"storeName" => "老蘿蔔店"
			"posId" => ""
			"closeDate" => null
			"openDate" => "2007-10-02"
			"areaName" => "大台北區"
			"storeKey" => "1060017"
		]
		*
		
		try
		{
			#取回的close date已+8
			#八方不含蘿蔔(因storeNo是相同的,且不用顯示,若要顯示時只有特殊的蘿蔔要處理)
			$store = $this->_repository->getStoreList($brand, $opCenter, $userAreaIds);
			
			$store = $this->_filterActiveStoreByDate($store, $stDate, $endDate);
			
			#為避免訂貨沒有更新POS Id, 因門店已有濾權限, 全取即可
			$ezorderPosIds = $this->_getPosIdFromEzOrder($brand);
			
			return $this->_formatStoreOutput($store, $ezorderPosIds);
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('讀取門店資料失敗');
		}
	} */
	
	/* Get store data by brand with LB stores(月初報表或訂貨才會顯示特殊的蘿蔔店, 其它目前沒有顯示)
	 * @params: int
	 * @params: array
	 * @return: array
	 */
	/* public function getStoreListWithLb($brand, $opCenter, $userAreaIds, $stDate = NULL, $endDate = NULL)
	{
		try
		{
			#只有門店才過濾營運中心, 用門店濾資料
			#取回data已排除開閉店
			$storeList = $this->getStoreList($brand, $opCenter, $userAreaIds, $stDate, $endDate);
			
			#八方北廠才有蘿蔔
			if ($brand == Brand::BAFANG)
			{
				$lbStoreList = $this->_repository->getLbStoreList($brand, $opCenter, $userAreaIds);
				
				$lbStoreList = $this->_filterActiveStoreByDate($lbStoreList, $stDate, $endDate);
				
				$lbStoreList = $this->_formatStoreOutput($lbStoreList);
				
				return $this->_mergeStoreOutput($storeList, $lbStoreList);
			}
			else
				return $storeList;
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('讀取門店資料失敗');
		}
	} */
	
	/********************** Filter Or Format Features **********************/
	/* 開閉店排除依日期
	 * @params: array
	 * @return: array
	 */
	/* private function _filterActiveStoreByDate($storeList, $stDate, $endDate)
	{
		#排除閉店:有值才檢查,start/end都要有
		if (! empty($stDate) && ! empty($endDate))
		{
			#明日開店,前一天可訂貨, 故要加一天
			$stDate	= Carbon::parse($stDate);
			$endDate= Carbon::parse($endDate)->addDay();
			
			$storeList = collect($storeList)->reject(function($item, $key) use($stDate, $endDate) {
				
				$openDate 	= empty($item['openDate']) ? NULL : Carbon::parse($item['openDate']);
				$closeDate 	= empty($item['closeDate']) ? NULL : Carbon::parse($item['closeDate']);
				
				#排除在開始時間前已閉店
				if (! is_null($closeDate) && $closeDate->lte($stDate))
					return TRUE;
				
				#排除在結束時間後才開店
				if (! is_null($openDate) && $openDate->gt($endDate))
					return TRUE;
				
				return FALSE;
			})->toArray();
		}
		
		return $storeList;
	} */
	
	/* 排除廠區學區店(因依情境不同手動呼叫,只針對沒有POS的)
	 * 銷售才會用到
	 * @params: array
	 * @return: array
	 */
	/* public function filterFactoryStore($storeList)
	{
		#ezorder定義的名單,全濾不影響
		#$excepts = config('web.ezorder.store');
		#$excepts = collect($excepts)->forget('code')->flatten()->all();
		
		#只濾除沒POS的
		return collect($storeList)->reject(function($item, $key) {
			return empty($item['posId']) OR $item['posId'] == 'null';
		})->toArray();
	} */
	
	/* 排除類型(依config,因pos及訂貨定義不同, 只能用名稱濾)
	 * @params: array
	 * @return: array
	 */
	/* public function filterStoreByTypeName($storeList, $type = [])
	{
		$configType = config('web.sales.shop.type');
		$configTypeKeys = array_keys($configType);
		
		$type = collect($type);
		$isAll = ($type->isEmpty() OR collect($configTypeKeys)->diff($type)->isEmpty());
		
		if ($isAll)
			return $storeList;
		
		$typeName = data_get($configType, "$type[0]");
		
		#因id不同,只能用Name過濾
		return collect($storeList)->filter(function($item, $key) use($typeName) {
			return $item['typeName'] == $typeName;
		})->toArray();
	} */
	
	/* Get pos id from ezorder
	 * @params: array
	 * @return: array
	 */
	/* private function _getPosIdFromEzOrder($brand)
	{
		$ids = $this->_repository->getPosIdFromEzOrder($brand);
		
		$ids = collect($ids)->mapWithKeys(function($item, $key){
			return [$item['storeKey'] => $item['posId']];
		})->all();
		
		return array_filter($ids);
	} */
	
	/* Format store output
	 * @params: array
	 * @return: array
	 */
	/* private function _formatStoreOutput($storeList, $ezorderPosIds = [])
	{
		#To key-value
		#因訂貨功能不需要POS Id,但此階段不能先排除,因不知道是誰來呼叫
		$store = collect($storeList)->map(function($item, $key) use($ezorderPosIds) {
			
			#因有包含蘿蔔, 故要用No來當Key => 只有八方, 御廚不適用, 最後一碼 1=>八方, 2=>蘿蔔
			#台北:10碼, 高雄:9碼(八方/蘿蔔已合併)=>全處理成7碼與舊系統同,才好mapping
			#有些No沒有TP/KH要注意
			$item['storeKey'] = $this->buildStoreKey($item['storeNo']);
			
			$ezPosId = data_get($ezorderPosIds, $item['storeKey'], '');
			
			if (empty($item['posId']) OR $item['posId'] == 'null')
				$item['posId'] =  '';
			
			$item['posId'] =  empty($ezPosId) ? $item['posId'] : $ezPosId;
			
			$area = AreaLib::toArea(intval($item['areaId']));
			
			$item['storeId']	= intval($item['storeId']);
			$item['areaId']		= $area->value;
			$item['areaName'] 	= $area->label();
			
			return $item;
		})->sortBy('areaId')->values()->all();
		
		return $store;
	} */
	
	/* Format store output
	 * @params: array
	 * @return: array
	 */
	/* private function _mergeStoreOutput($storeList, $lbStoreList)
	{
		$storeKeys = collect($storeList)->pluck('storeKey')->toArray();
		
		#取出單蘿蔔店(如老蘿蔔沒有八方,所以沒有對應的storeKey)
		$lbSpecials = collect($lbStoreList)->filter(function($item, $key) use($storeKeys) {
			return !in_array($item['storeKey'], $storeKeys);
		});
		
		#Merge獨立的蘿蔔店
		$stores = $lbSpecials->merge($storeList)->sortBy('areaId')->toArray();
		
		return $stores;
	}
	 */
	/* Build store key(新舊系統Mapping)
	 * @params: string
	 * @return: array
	 */
	/* public function buildStoreKey($storeNo)
	{
		#特殊的蘿蔔店,因不符規則編碼規則,故要先處理
		$lbSpecialStoreNos = config('web.purchase.store.lbSpecialStore');
		$convertNo = data_get($lbSpecialStoreNos, $storeNo, NULL);
		$storeNo = empty($convertNo) ? $storeNo : $convertNo;
		
		#新系統有前置碼/八方有蘿蔔尾碼1&2
		$storeKey = Str::of($storeNo)->replaceStart('TP', '')->replaceStart('KH', '')->replaceStart('TS', '')->replaceStart('RL', '');
		$storeKey = Str::take($storeKey, 7);
		
		return $storeKey;
	} */
	
	/* 過濾不計算的門店(如員購)
	 * @params: string
	 * @return: array
	 */
	/* public function filterOrderByStoreNo($brandId, $baseData)
	{
		$excepts = config("web.purchase.store.except.{$brandId}");
		
		$result = collect($baseData)->filter(function($item, $key) use($excepts){
			return ! in_array($item['storeNo'], $excepts);
		});
		
		return $result;
	} */
	
	/* 過濾門店By posId (銷售功能呼叫用)
	 * @params: array
	 * @params: array
	 * @return: array
	 */
	/* public function filterStoreByPosId($storeList, $posIds)
	{
		return collect($storeList)->reject(function($item, $key) use($posIds){
			return in_array($item['posId'], $posIds);
		})->all();
	} */
	
	
	
	
}