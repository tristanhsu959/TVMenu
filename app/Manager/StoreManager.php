<?php

namespace App\Manager;

use App\Manager\Repositories\StoreRepository;
use App\Libraries\Purchase\AreaLib;
use App\Enums\OpCenter;
use App\Enums\Brand;
use App\Enums\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

/* 因同步訂貨,故抽出獨立, 不分銷售及訂貨 */
/* 個自的定義放在自己的Manager */
class StoreManager
{
	public function __construct(protected StoreRepository $_repository)
	{
	}
	
	/******************** Store Main Feature ********************/
	/* Get store data by brand
	 * @params: int
	 * @params: array
	 * @return: array
	 */
	public function getStoreList($brand, $allowOpCenterIds, $allowAreaIds, $stDate = NULL, $endDate = NULL)
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
		*/
		
		try
		{
			#取門店不含蘿蔔
			list($storeList, $lbStoreList) = $this->_getStoreData($brand, $allowOpCenterIds, $allowAreaIds);
			
			#只需要storeList
			$storeList = $this->filterActiveStoreByDate($storeList, $stDate, $endDate);
			
			return $this->_formatStoreOutput($brand, $storeList);
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('讀取門店資料失敗');
		}
	}
	
	/* Get store data by brand with LB stores(月初報表或訂貨才會顯示特殊的蘿蔔店, 其它目前沒有顯示)
	 * @params: int
	 * @params: array
	 * @return: array
	 */
	public function getStoreListWithLb($brand, $allowOpCenterIds, $allowAreaIds, $stDate = NULL, $endDate = NULL)
	{
		try
		{
			#蘿蔔店獨立取,為避免取錯店名或目關資訊
			#取回data已排除開閉店
			list($storeList, $lbStoreList) = $this->_getStoreData($brand, $allowOpCenterIds, $allowAreaIds);
			
			$storeList = $this->filterActiveStoreByDate($storeList, $stDate, $endDate);
			$storeList = $this->_formatStoreOutput($brand, $storeList);
			
			#八方北廠才有蘿蔔
			if (empty($lbStoreList))
				return $storeList;
			else
			{
				$lbStoreList = $this->filterActiveStoreByDate($lbStoreList, $stDate, $endDate);
				$lbStoreList = $this->_formatStoreOutput($brand, $lbStoreList);
				
				return $this->_mergeLbStore($storeList, $lbStoreList);
			}

		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('讀取門店資料失敗');
		}
	}
	
	/* 取門店及蘿蔔分開
	 * @params: int
	 * @params: array
	 * @return: array
	 */
	public function getLbStoreList($brand, $allowOpCenterIds, $allowAreaIds, $stDate = NULL, $endDate = NULL)
	{
		try
		{
			#取門店不含蘿蔔
			list($storeList, $lbStoreList) = $this->_getStoreData($brand, $allowOpCenterIds, $allowAreaIds);
			
			$lbStoreList = $this->filterActiveStoreByDate($lbStoreList, $stDate, $endDate);
			$lbStoreList = $this->_formatStoreOutput($brand, $lbStoreList);
			
			return $lbStoreList;
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('讀取門店資料失敗');
		}
	}
	
	/* Get store data by brand
	 * @params: int
	 * @params: array
	 * @return: array
	 */
	private function _getStoreData($brand, $allowOpCenterIds, $allowAreaIds)
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
		*/
		
		$allStoreList = $this->_repository->getStoreList($brand, $allowOpCenterIds, $allowAreaIds);
		
		#過濾出主要門店(八方或御廚或芳珍)及蘿蔔
		$storeGroup = collect($allStoreList)->groupBy('brandNo')->toArray();
		
		$mainBrandCode 	= $brand->shortCode();
		$lbBrandCode	= Brand::LUOBO->shortCode(); 
		
		$mainStores = data_get($storeGroup, $mainBrandCode, []);
		$lbStores 	= data_get($storeGroup, $lbBrandCode, []);
		return [$mainStores, $lbStores];
	}
	
	/* 開閉店排除依日期
	 * @params: array
	 * @return: array
	 */
	public function filterActiveStoreByDate($storeList, $stDate, $endDate)
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
	}
	
	/* 開閉店排除
	 * @params: array
	 * @return: array
	 */
	public function filterActiveStoreByCloseDate($storeList)
	{
		#只排除有close date的店
		return collect($storeList)->filter(function($item, $key) {
				return empty($item['closeDate']);
		})->all();
	}
	
	/* Format store output
	 * @params: array
	 * @return: array
	 */
	private function _formatStoreOutput($brand, $storeList)
	{
		$ezorderPosIds = $this->getPosIdFromEzOrderByBrand($brand);
		
		#To key-value
		#因訂貨功能不需要POS Id,但此階段不能先排除,因不知道是誰來呼叫
		$store = collect($storeList)->map(function($item, $key) use($ezorderPosIds) {
			
			$item['storeKey'] = $this->buildStoreKey($item['storeNo']);
			
			#ez無值取原來的,芳珍就不會有posid在ezorder
			$ezPosId = data_get($ezorderPosIds, $item['storeKey'], '');
			
			if (empty($item['posId']) OR $item['posId'] == 'null')
				$item['posId'] =  '';
			
			$item['posId'] =  empty($ezPosId) ? $item['posId'] : $ezPosId;
			
			$area = AreaLib::toArea(intval($item['areaId']));
			
			$item['storeId']	= intval($item['storeId']);
			$item['areaId']		= $area->value;
			$item['areaName'] 	= $area->label();
			
			$item['district']	= Str::substr($item['district'], 0, 3);
			
			return $item;
		})->unique('storeKey')->sortBy('areaId')->values()->all(); #芳珍會有重複的店
		
		return $store;
	}
	
	/* Get pos id from ezorder
	 * @params: array
	 * @return: array
	 */
	public function getPosIdFromEzOrderByBrand($brand)
	{
		$ids = $this->_repository->getPosIdFromEzOrder($brand);
		
		$ids = collect($ids)->mapWithKeys(function($item, $key){
			return [$item['storeKey'] => $item['posId']];
		})->all();
		
		return array_filter($ids);
	}
	
	/* Build store key(新舊系統Mapping)
	 * @params: string
	 * @return: array
	 */
	public function buildStoreKey($storeNo)
	{
		#特殊的蘿蔔店,因不符規則編碼規則,故要先處理
		$lbSpecialStoreNos = config('web.purchase.store.lbSpecialStore');
		$convertNo = data_get($lbSpecialStoreNos, $storeNo, NULL);
		$storeNo = empty($convertNo) ? $storeNo : $convertNo;
		
		#新系統有前置碼/八方有蘿蔔尾碼1&2
		#因有包含蘿蔔, 故要用No來當Key => 只有八方, 御廚不適用, 最後一碼 1=>八方, 2=>蘿蔔
		#台北:10碼, 高雄:9碼(八方/蘿蔔已合併)=>全處理成7碼與舊系統同,才好mapping
		#有些No沒有TP/KH要注意
		#$storeKey = Str::of($storeNo)->replaceStart('TP', '')->replaceStart('KH', '')->replaceStart('TS', '')->replaceStart('RL', '');
		$storeKey = Str::of($storeNo)->replaceMatches('/^(TP|KH|TS|RL)/', '');
		$storeKey = Str::take($storeKey, 7);
		
		return $storeKey;
	}
	
	/* Format store output
	 * @params: array
	 * @return: array
	 */
	private function _mergeLbStore($storeList, $lbStoreList)
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
	
	/********************** Store Main Feature End **********************/
	
	
	/********************** Filter Or Format Features **********************/
	
	/* 排除廠區學區店(因依情境不同手動呼叫,只針對沒有POS的)
	 * 銷售才會用到
	 * @params: array
	 * @return: array
	 */
	public function filterFactoryStore($brand, $storeList)
	{
		#濾除沒POS的或廠區學區店-訂貨系統定義
		return collect($storeList)->reject(function($item, $key) {
			return empty($item['posId']) OR $item['posId'] == 'null';
		})->toArray();
	}
	
	/* 排除廠區學區店(因依情境不同手動呼叫,只針對沒有POS的)
	 * 銷售才會用到
	 * @params: array
	 * @return: array
	 */
	public function filterEzorderFactoryStore($brand, $storeList)
	{
		#ezorder定義的名單,有另調整過
		$brandId = $brand->value;
		$excepts = config("web.purchase.store.factoryStore.{$brandId}", []);
		
		#濾除沒POS的或廠區學區店
		return collect($storeList)->reject(function($item, $key) use($excepts) {
			return in_array($item['storeKey'], $excepts);
		})->toArray();
	}
	
	/* 排除類型(依config,因pos及訂貨定義不同, 只能用名稱濾)
	 * @params: array
	 * @return: array
	 */
	public function filterStoreByTypeName($storeList, $type = [])
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
	}
	
	/* 過濾不計算的門店(如員購)
	 * @params: string
	 * @return: array
	 */
	public function filterOrderByStoreNo($brandId, $baseData)
	{
		$excepts = config("web.purchase.store.except.{$brandId}");
		
		$result = collect($baseData)->filter(function($item, $key) use($excepts){
			return ! in_array($item['storeNo'], $excepts);
		});
		
		return $result;
	}
	
	/* 過濾門店By posId (銷售功能呼叫用)
	 * @params: array
	 * @params: array
	 * @return: array
	 */
	public function filterStoreByPosId($storeList, $posIds)
	{
		return collect($storeList)->reject(function($item, $key) use($posIds){
			return in_array($item['posId'], $posIds);
		})->all();
	}
	
	/* 合併門店及蘿蔔
	 * @params: array
	 * @return: array
	 */
	public function mergeLbStoreByBrandNo($brand, $allStoreList)
	{
		#必須要有brandNo, storeKey fields
		#過濾出主要的八方或御廚或芳珍
		$mainBrandCode 	= $brand->shortCode();
		$lbBrandCode	= Brand::LUOBO->shortCode();
		
		$storeGroup		= collect($allStoreList)->groupBy('brandNo');
		$mainStoreList 	= $storeGroup->get($mainBrandCode);
		$lbStoreList 	= $storeGroup->get($lbBrandCode);
		
		$storeKeys = collect($mainStoreList)->pluck('storeKey')->toArray();
		
		#取出單蘿蔔店(如老蘿蔔沒有八方,所以沒有對應的storeKey)
		$lbSpecials = collect($lbStoreList)->filter(function($item, $key) use($storeKeys) {
			return !in_array($item['storeKey'], $storeKeys);
		});
		
		#Merge獨立的蘿蔔店
		$stores = $mainStoreList->merge($lbSpecials)->toArray();
		
		return $stores;
	}
}