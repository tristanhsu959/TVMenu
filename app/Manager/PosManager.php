<?php

namespace App\Manager;

use App\Facades\AppManager;
use App\Manager\Repositories\PosRepository;
use App\Libraries\Sales\AreaLib;
use App\Libraries\HelperLib;
use App\Enums\OpCenter;
use App\Enums\Brand;
use App\Enums\Area;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

/* 改為Singleton
門店處理 Common function
處理訂單門市過濾/補全門店
*/
class PosManager
{
	public function __construct(protected PosRepository $_repository)
	{
	}
	
	/******************** 屬銷售的定義放在POS Manager ********************/
	/* 
	 * @params: 
	 * @return: boolean
	 */
	public function getAllowOpCenters()
	{
		#銷售不分營運中心
		return OpCenter::toValueArray();
	}
	
	/* 
	 * @params: 
	 * @return: boolean
	 */
	public function getAllowAreas($filterAreas = [])
	{
		$currentUser 	= AppManager::getCurrentUser();
		$authAreas 		= $currentUser->getSalesAreaPermissions();
		
		if (empty($filterAreas))
			return $authAreas;
		else
			return array_map('intval', $filterAreas);
	}
		
	/* Filter data by shop
	 * @params: collection
	 * @return: array
	 */
	public function filterDataByExceptStore($brand, $data)
	{
		$brandId = $brand->value;
		$excepts = config("web.sales.shop.except.{$brandId}");
		
		$result = collect($data)->filter(function($item, $key) use($excepts){
			return ! in_array($item['shopId'], $excepts);
		});
		
		return $result;
	}
	
	/* 排除特殊複合(屬銷售定義非通用,故不寫在StoreManager)
	 * @params: collection
	 * @return: array
	 */
	public function filterSpecialStore($brand, $storeList)
	{
		#御廚才有影響
		$brandId = $brand->value;
		$excepts = config("web.sales.shop.dualBrandedExceptStoreKey.{$brandId}", []);
		
		$result = collect($storeList)->filter(function($item, $key) use($excepts){
			return ! in_array($item['storeKey'], $excepts);
		})->toArray();
		
		return $result;
	}
	
	/* 補全門店判別(門店已改同步訂貨)
	 * @params: array
	 * @return: array
	 */
	/* public function getFillOutStore($activeShopList, $saleShopIds)
	{
		#改用active shop來判過濾即可
		$result = collect($activeShopList)->filter(function($item, $key) use($saleShopIds) {
			#過濾出無銷售且為active門店
			return ! in_array($item['posId'], $saleShopIds);
		});
		
		return $result;
	} */
	
	/* 判別複合店
	 * @params: array
	 * @return: array
	 */
	public function isDualBranded($posId)
	{
		$dualBrandedShopIds = config('web.sales.shop.dualBrandedId');
		#八方及御廚都判斷, 不用特別判別Brand
		
		$bafang	= array_keys($dualBrandedShopIds);
		$buygood= array_values($dualBrandedShopIds);
		
		return in_array($posId, $bafang) OR in_array($posId, $buygood);
	}
	
	/* 取複合店
	 * @params: array
	 * @return: array
	 */
	public function getDualBrandedMappingId($posId)
	{
		#八方及御廚都判斷, 不用特別判別Brand
		$bafangMapping = config('web.sales.shop.dualBrandedId');
		$buygoodMapping = array_flip($bafangMapping);
		
		#基本上都是御廚posid要抓八方,所以先判別buygood
		$mapId = data_get($buygoodMapping, $posId, 0);
		
		if (! empty($mapId))
			return $mapId;
		
		return data_get($bafangMapping, $posId, 0);
	}
	
	/* 取全部店家(含閉店)
	 * @params: enum
	 * @params: array
	 * @params: array
	 * @params: string
	 * @return: array
	 */
	/* public function getAllStores($brand, $userAreaIds, $type = FALSE, $name = FALSE)
	{
		try
		{
			$cacheKey = HelperLib::buildCacheKey([$brand->value, $userAreaIds, $type, $name, 'pos', 'all-stores']);
			
			if (Cache::has($cacheKey))
				return Cache::get($cacheKey); #cache data is response format
			
			#會Filter區域權限及無效門店
			$storeList = $this->_repository->getStoreList($brand, $userAreaIds, $type, $name); #all shops
			$storeList = $this->_formatStores($storeList);
			
			Cache::put($cacheKey, $storeList, now()->addMinutes(60));
			
			return $storeList;
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('讀取門店資料發生錯誤');
		}
	} */
	
	/* 取有效店家
	 * @params: enum
	 * @params: array
	 * @params: array
	 * @params: string
	 * @return: array
	 */
	/* public function getActiveStores($brand, $userAreaIds, $type = FALSE, $name = FALSE)
	{
		try
		{
			#必須要有全部的值, 尤其是areaid
			$cacheKey = HelperLib::buildCacheKey([$brand->value, $userAreaIds, $type, $name, 'pos', 'active-stores']);
			
			if (Cache::has($cacheKey))
				return Cache::get($cacheKey); #cache data is response format
			
			#會Filter區域權限及無效門店
			$storeList = $this->_repository->getHptransStoreList($brand, $userAreaIds, $type, $name); #all shops
			$storeList = $this->_formatStores($storeList);
			
			Cache::put($cacheKey, $storeList, now()->addMinutes(60));
			
			return $storeList;
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('讀取門店資料發生錯誤');
		}
	} */
	
	/* 格式化及轉換
	 * @params: array
	 * @return: array
	 */
	/* private function _formatStores($storeList)
	{
		$result = $storeList->map(function($item, $key){
			$item['shopName'] 	= Str::replace($item['shopId'], '', $item['shopName']); #去除Name前置的shop id
			$item['areaId']		= AreaLib::toId($item['areaId']);
			$item['areaName']	= (Area::tryFrom($item['areaId']))->label();
			return $item;
		})->sortBy('areaId')->values()->all();
		
		return $result;
	} */	
		
	
}