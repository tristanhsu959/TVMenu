<?php

namespace App\Services;

use App\Facades\PurchaseManager;
use App\Repositories\PurchaseProductRepository;
use App\Libraries\ResponseLib;
use App\Enums\Brand;
use App\Enums\OpCenter;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Exception;
use Log;

class PurchaseProductService
{
	public function __construct(protected PurchaseProductRepository $_repository)
	{
	}
	
	/* 取出貨產品設定清單(要整合Name)
	 * @params: 
	 * @return: array
	 */
	public function getList()
	{
		try
		{
			$opCenterIds = OpCenter::getAll();
			$bfBrandId = Brand::BAFANG->value;
			$bgBrandId = Brand::BUYGOOD->value;
			
			#先取訂貨的產品資料,因本地設定只有ShortCode, 沒有Name
			$productMapping[$bfBrandId] = PurchaseManager::getProductShortCodeMapping(Brand::BAFANG, $opCenterIds);
			$productMapping[$bgBrandId] = PurchaseManager::getProductShortCodeMapping(Brand::BUYGOOD, $opCenterIds);
			
			#轉成key-value
			$productMapping = collect($productMapping)->map(function($items, $key){
				return collect($items)->mapWithKeys(function($item, $key){
					return [$item['productNo'] => $item['productName']];
				})->toArray();
			})->toArray();
			
			#取enabled的可查詢產品
			$list = $this->_repository->getSetting();
			
			$list = collect($list)->groupBy('brandId')->map(function($items, $key) use($productMapping) {
				return $items->map(function($item, $key) use($productMapping){
					$item['productName'] = data_get($productMapping, "{$item['brandId']}.{$item['productCode']}", '');
					unset($item['brandId']);
					return $item;
				});
			})->toArray();
			
			return ResponseLib::initialize($list)->success();
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			return ResponseLib::initialize()->fail('讀取出貨產品設定清單發生錯誤');
		}
	}
	
	/* Get product for options from new order system(called by viewmodel for options)
	 * called by update
	 * @params: 
	 * @return: array
	 */
	public function getProductOptions()
	{
		#設定選項清單
		try
		{
			$opCenterIds = OpCenter::getAll();
			$bfBrandId = Brand::BAFANG->value;
			$bgBrandId = Brand::BUYGOOD->value;
			
			#要分開取, 因short code是不分brand
			$list[$bfBrandId] = PurchaseManager::getProductShortCodeMapping(Brand::BAFANG, $opCenterIds);
			$list[$bgBrandId] = PurchaseManager::getProductShortCodeMapping(Brand::BUYGOOD, $opCenterIds);
			
			#下架沒有被設定成stop, 但erpNo似乎會是空值, 目前是全取
			$list = collect($list)->map(function($items, $brandId) {
				
				return collect($items)->map(function($item, $key) use($brandId){
					
					#取得config自訂的分類,只是為了UI上方便查找
					$group = PurchaseManager::getGroupByShortCode($brandId, $item['productNo']);
					
					return array_merge($item, $group);
				})->groupBy('groupId')->map(function($items, $key){
					$temp['groupName'] 	= $items->pluck('groupName')->first();
					$temp['products']	= $items->mapWithKeys(function($item, $key) {
						return [$item['productNo'] => $item['productName']];
					})->toArray();
					
					return $temp;
				});
			})->toArray();
			
			return $list;
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			return [];
		}
	}
	
	/* 取設定(Get ALL)
	 * @params: 
	 * @return: array
	 */
	public function getSetting()
	{
		try
		{
			$list = $this->_repository->getSetting();
			$list = collect($list)->groupBy('brandId')->map(function($items, $key){
				return $items->pluck('productCode');
			})->toArray();
			
			#default init
			if (empty($list[Brand::BAFANG->value]))
				$list[Brand::BAFANG->value] = [];
			
			if (empty($list[Brand::BUYGOOD->value]))
				$list[Brand::BUYGOOD->value] = [];
			
			return ResponseLib::initialize($list)->success();
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			return ResponseLib::initialize()->fail('讀取出貨產品設定清單時發生錯誤');
		}
	}
	
	/* Update product
	 * @params: int
	 * @params: array
	 * @return: array
	 */
	public function updateSetting($productCodes)
	{
		try
		{
			$this->_repository->update($productCodes);
			
			return ResponseLib::initialize()->success();
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			return ResponseLib::initialize()->fail('出貨產品設定失敗');
		}
	}
}
