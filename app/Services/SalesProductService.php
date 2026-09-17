<?php

namespace App\Services;

use App\Repositories\SalesProductRepository;
use App\Libraries\ResponseLib;
use App\Enums\Brand;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Exception;
use Log;

class SalesProductService
{
	public function __construct(protected SalesProductRepository $_repository)
	{
	}
	
	/* 取設定清單
	 * @params: 
	 * @return: array
	 */
	public function getList()
	{
		try
		{
			$list = $this->_repository->getSetting();
			
			$list = collect($list)->groupBy('brandId')->map(function($items, $key) {
				return $items->map(function($item, $key){
					$temp['category'] 	= config("web.sales.category.{$item['brandId']}.{$item['category']}");
					$temp['name']		= $item['productName'];
					
					return $temp;
				});
			})->toArray();
			
			return ResponseLib::initialize($list)->success();
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			return ResponseLib::initialize()->fail('讀取銷售產品設定清單時發生錯誤');
		}
	}
	
	/* Get product for options from product table
	 * @params: int
	 * @return: array
	 */
	public function getProductList()
	{
		try
		{
			#取產品料號有設定的產品清單
			$list = $this->_repository->getErpProductList();
			
			$list = collect($list)->groupBy('brandId')->map(function($items, $brandId) {
				
				return $items->groupBy('categoryId')->map(function($items, $catId) use($brandId) {
					$temp['catName'] = config("web.sales.category.{$brandId}.{$catId}");
					$temp['products'] = $items->map(function($item, $key) {
						unset($item['brandId']);
						unset($item['categoryId']);
						
						return $item;
					})->toArray();
					
					return $temp;
				})->sortKeys();
				
			})->toArray();
			
			/* $list = collect($list)->groupBy('brandId')->map(function($items, $key) {
				return $items->groupBy(function($item, $key){
						return config("web.sales.category.{$item['brandId']}.{$item['categoryId']}");
					})->map(function($items, $key) {
						return $items->map(function($item, $key){
							unset($item['brandId']);
							unset($item['categoryId']);
						
							return $item;
						});
					});
				
			})->toArray(); */
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
			$list = collect($list)->groupBy('brandId')->map(function($items, $key) {
				return $items->pluck('productId');
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
			return ResponseLib::initialize()->fail('讀取銷售產品設定清單時發生錯誤');
		}
	}
	
	/* Update product
	 * @params: int
	 * @params: array
	 * @return: array
	 */
	public function updateSetting($productIds)
	{
		try
		{
			$this->_repository->update($productIds);
			
			return ResponseLib::initialize()->success();
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			return ResponseLib::initialize()->fail('銷售產品設定失敗');
		}
	}
}
