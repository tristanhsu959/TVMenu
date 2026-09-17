<?php

namespace App\Services;

use App\Repositories\SalesSettingRepository;
use App\Libraries\ResponseLib;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Exception;
use Log;

#20260419: deprecated, replace by sales product setting
class SalesSettingService
{
	
	public function __construct(protected SalesSettingRepository $_repository)
	{
	}
	
	/* 取設定清單(Get ALL)
	 * @params: 
	 * @return: array
	 */
	public function getList()
	{
		try
		{
			$list = $this->_repository->getList();
			$list = collect($list)->groupBy('salesBrandId')->toArray();
			
			return ResponseLib::initialize($list)->success();
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			return ResponseLib::initialize()->fail('讀取銷售設定清單發生錯誤');
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
	public function createSetting($id, $brandId, $name, $status, $productIds)
	{
		try
		{
			$this->_repository->insert($brandId, $name, $status, $productIds);
			
			return ResponseLib::initialize()->success();
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			return ResponseLib::initialize()->fail('銷售設定新增失敗');
		}
	}
	
	/* Get setting by id
	 * @params: int
	 * @return: array
	 */
	public function getSettingById($id)
	{
		try
		{
			$data = $this->_repository->getById($id);
			$data = collect($data)->groupBy('salesId')->map(function($items, $key){
				$items = collect($items);
				
				$temp['salesId'] 		= $items->pluck('salesId')->first();
				$temp['salesBrandId'] 	= $items->pluck('salesBrandId')->first();
				$temp['salesName'] 		= $items->pluck('salesName')->first();
				$temp['salesStatus'] 	= $items->pluck('salesStatus')->first();
				$temp['updateAt'] 		= $items->pluck('updateAt')->first();
				$temp['productIds'] 	= $items->pluck('productId')->values()->filter()->toArray();
				
				return $temp;
			})->first();
			
			return ResponseLib::initialize($data)->success();
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			return ResponseLib::initialize()->fail('讀取銷售設定時發生錯誤');
		}
	}
	
	/* Update Role
	 * @params: int
	 * @params: int
	 * @params: string
	 * @params: boolean
	 * @params: array
	 * @return: array
	 */
	public function updateSetting($id, $brandId, $name, $status, $productIds)
	{
		try
		{
			$this->_repository->update($id, $brandId, $name, $status, $productIds);
			
			return ResponseLib::initialize()->success();
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			return ResponseLib::initialize()->fail('編輯銷售設定失敗');
		}
	}
	
	/* Remove Role
	 * @params: int
	 * @return: array
	 */
	public function deleteSetting($id)
	{
		try
		{
			$this->_repository->remove($id);
			return ResponseLib::initialize()->success();
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			return ResponseLib::initialize()->fail('刪除銷售設定失敗');
		}
	}
	
}
