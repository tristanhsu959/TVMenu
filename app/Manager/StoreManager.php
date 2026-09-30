<?php

namespace App\Manager;

use App\Manager\Repositories\StoreRepository;
use App\Enums\Brand;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

/* EzOrder Store List */
class StoreManager
{
	public function __construct(protected StoreRepository $_repository)
	{
	}
	
	/******************** Store Main Feature ********************/
	/* Get store data by brand
	 * @params: array
	 * @return: array
	 */
	public function getList($brand)
	{
		try
		{
			$checkDay = Carbon::today()->addDay()->format('Y-m-d H:i:s'); 
			$list = $this->_getStores($brand, $checkDay);
			
			return $list;
		}
		catch(Exception $e)
		{
			Log::channel('apiStoreLog')->error('store manager', [$e->getMessage()]);
			throw new Exception('讀取門店資料失敗');
		}
	}
	
	/* Get store data by brand
	 * @params: array
	 * @return: array
	 */
	private function _getStores($brand, $checkDay)
	{
		/*0 => array:9 [
			"_id" => "19359"
			"brand" => "BUYGOOD"
			"storeId" => "100U002"
			"storeName" => "御廚中正濟南店"
			"closeDate" => null
		]
		*/
		$list = $this->_repository->getStoreList($brand, $checkDay);
		
		$list = collect($list)->map(function($item, $key){
			$temp['id'] 		= intval($item['_id']);
			$temp['brand'] 		= $item['brand'];
			$temp['storeId'] 	= $item['storeId']; #Store No
			$temp['storeName'] 	= $item['storeName'];
			
			return $temp;
		})->toArray();
		
		return $list;
	}
	
	/********************** Store Main Feature End **********************/
	
	
	/********************** Filter Or Format Features **********************/
	
	/* 排除廠區學區店(因依情境不同手動呼叫)
	 * 銷售才會用到
	 * @params: array
	 * @return: array
	 */
	public function filterFactoryStore($brand, $storeList)
	{
		#ezorder定義的名單,有另調整過
		$brandId = $brand->value;
		$excepts = config("web.purchase.store.factoryStore.{$brandId}", []);
		
		#濾除沒POS的或廠區學區店
		return collect($storeList)->reject(function($item, $key) use($excepts) {
			return in_array($item['storeKey'], $excepts);
		})->toArray();
	}
	
	
}