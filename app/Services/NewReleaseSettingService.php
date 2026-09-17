<?php

namespace App\Services;

use App\Repositories\NewReleaseSettingRepository;
use App\Libraries\ResponseLib;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Exception;
use Log;

class NewReleaseSettingService
{
	
	public function __construct(protected NewReleaseSettingRepository $_repository)
	{
	}
	
	/* 取Role清單(Get ALL)
	 * @params: 
	 * @return: array
	 */
	public function getList()
	{
		try
		{
			$list = $this->_repository->getList();
			$list = collect($list)->groupBy('releaseBrandId')->toArray();
			/* $list = collect($list)->groupBy('releaseId')->map(function($items, $key){
				$temp['releaseId'] 		= $items->pluck('releaseId')->first();
				$temp['releaseBrandId'] = $items->pluck('releaseBrandId')->first();
				$temp['releaseName'] 	= $items->pluck('releaseName')->first();
				$temp['releaseSaleDate']= $items->pluck('releaseSaleDate')->first();
				$temp['releaseTaste'] 	= $items->pluck('releaseTaste')->first();
				$temp['releaseStatus'] 	= $items->pluck('releaseStatus')->first();
				$temp['updateAt'] 		= $items->pluck('updateAt')->first();
				$temp['productIds'] 	= $items->pluck('productId')->values()->filter()->toArray();
				
				return $temp;
			})->groupBy('releaseBrandId')->toArray(); */
			
			return ResponseLib::initialize($list)->success();
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			return ResponseLib::initialize()->fail('讀取新品設定時發生錯誤');
		}
	}
	
	/* Get product for options
	 * @params: int
	 * @return: array
	 */
	public function getProductList()
	{
		try
		{
			$list = $this->_repository->getProductSettings();
			$list = collect($list)->groupBy('productBrandId')->map(function($items, $key){
				return $items->groupBy('productCategory')->map(function($items, $key){
					return $items->map(function($item, $key){
						$brandId = $item['productBrandId'];
						$catId = $item['productCategory'];
						$item['categoryName'] = config("web.sales.category.{$brandId}.{$catId}");
						return $item;
					});
					return $items;
				});
				
				return $items;
			})->toArray();
			
			return $list;
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			return [];
		}
	}
	
	/* Create new item
	 * @params: string
	 * @params: int
	 * @params: array
	 * @params: array
	 * @return: array
	 */
	public function createNewRelease($id, $brandId, $productIds, $name, $saleDate, $tasteKeyWord, $status)
	{
		try
		{
			$tastes = Str::of($tasteKeyWord)->explode("\r\n")
				->reject(function ($value, $key) {
					return empty($value);
			})->toArray();
			
			$this->_repository->insert($brandId, $productIds, $name, $saleDate, $tastes, $status);
			
			return ResponseLib::initialize()->success();
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			return ResponseLib::initialize()->fail('新品設定新增失敗');
		}
	}
	
	/* Get product by id
	 * @params: int
	 * @return: array
	 */
	public function getNewReleaseById($id)
	{
		try
		{
			$data = $this->_repository->getById($id);
			$data = collect($data)->groupBy('releaseId')->map(function($items, $key){
				$items = collect($items);
				
				$temp['releaseId'] 		= $items->pluck('releaseId')->first();
				$temp['releaseBrandId'] = $items->pluck('releaseBrandId')->first();
				$temp['releaseName'] 	= $items->pluck('releaseName')->first();
				$temp['releaseSaleDate']= $items->pluck('releaseSaleDate')->first();
				$temp['releaseTaste'] 	= $items->pluck('releaseTaste')->first();
				$temp['releaseStatus'] 	= $items->pluck('releaseStatus')->first();
				$temp['updateAt'] 		= $items->pluck('updateAt')->first();
				$temp['productIds'] 	= $items->pluck('productId')->values()->filter()->toArray();
				
				return $temp;
			})->first();
			
			return ResponseLib::initialize($data)->success();
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			return ResponseLib::initialize()->fail('讀取新品設定時發生錯誤');
		}
	}
	
	/* Update Role
	 * @params: int
	 * @params: string
	 * @params: int
	 * @params: array
	 * @params: array
	 * @return: array
	 */
	public function updateNewRelease($id, $brandId, $productIds, $name, $saleDate, $tasteKeyWord, $status)
	{
		try
		{
			$tastes = Str::of($tasteKeyWord)->explode("\r\n")
				->reject(function ($value, $key) {
					return empty($value);
			})->toArray();
			
			$this->_repository->update($id, $brandId, $productIds, $name, $saleDate, $tastes, $status);
			
			return ResponseLib::initialize()->success();
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			return ResponseLib::initialize()->fail('編輯新品設定失敗');
		}
	}
	
	/* Remove Role
	 * @params: int
	 * @return: array
	 */
	public function deleteNewRelease($id)
	{
		try
		{
			$this->_repository->remove($id);
			return ResponseLib::initialize()->success();
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			return ResponseLib::initialize()->fail('刪除新品設定失敗');
		}
	}
	
}
