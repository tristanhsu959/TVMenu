<?php

namespace App\Services;

use App\Repositories\ProductRepository;
use App\Libraries\ResponseLib;
use App\Events\ProductRemoved;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Exception;
use Log;

class ProductService
{
	
	public function __construct(protected ProductRepository $_repository)
	{
	}
	
	/* 取Product清單(Get ALL)
	 * @params: 
	 * @return: array
	 */
	public function getList()
	{
		try
		{
			$list = $this->_repository->getList();
			
			$list = collect($list)->map(function($item, $key){
				$brandId = $item['productBrandId'];
				$catId = $item['productCategory'];
				
				$item['categoryName'] = config("web.sales.category.{$brandId}.{$catId}");
				
				return $item;
			})->sortBy('productCategory')->groupBy('productBrandId')->toArray();
			
			return ResponseLib::initialize($list)->success();
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			return ResponseLib::initialize()->fail('讀取產品料號設定時發生錯誤');
		}
	}
	
	/* Create product
	 * @params: enums
	 * @params: int
	 * @params: string
	 * @params: array
	 * @params: array
	 * @return: array
	 */
	public function createProduct($brandId, $category, $name, $primaryNo, $secondaryNo)
	{
		try
		{
			$primaryNo = Str::of($primaryNo)->explode("\r\n")
				->reject(function ($value, $key) {
					return empty($value);
			})->toArray();
			
			$secondaryNo = Str::of($secondaryNo)->explode("\r\n")
				->reject(function ($value, $key) {
					return empty($value);
			})->toArray();
			
			$this->_repository->insert($brandId, $category, $name, $primaryNo, $secondaryNo);
			
			return ResponseLib::initialize()->success();
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			return ResponseLib::initialize()->fail('新增產品料號失敗');
		}
	}
	
	/* Get product by id
	 * @params: int
	 * @return: array
	 */
	public function getProductById($id)
	{
		try
		{
			$data = $this->_repository->getById($id);
			
			$result = [];
			#重整資料
			if (! empty($data))
			{
				$collection = collect($data);
				
				$result['productId'] 		= $collection->pluck('productId')->first();
				$result['productBrandId'] 	= $collection->pluck('productBrandId')->first();
				$result['productCategory'] 	= $collection->pluck('productCategory')->first();
				$result['productName'] 		= $collection->pluck('productName')->first();
				$result['primaryNo'] 		= $collection->where('isPrimary', TRUE)->pluck('erpNo')->toArray();
				$result['secondaryNo'] 		= $collection->where('isPrimary', FALSE)->pluck('erpNo')->toArray();
				$result['productCategory'] 	= $collection->pluck('productCategory')->first();
			}
			
			return ResponseLib::initialize($result)->success();
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			return ResponseLib::initialize()->fail('讀取產品料號設定時發生錯誤');
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
	public function updateProduct($id, $brandId, $category, $name, $primaryNo, $secondaryNo)
	{
		try
		{
			$primaryNo = Str::of($primaryNo)->explode("\r\n")
				->reject(function ($value, $key) {
					return empty($value);
			})->toArray();
			
			$secondaryNo = Str::of($secondaryNo)->explode("\r\n")
				->reject(function ($value, $key) {
					return empty($value);
			})->toArray();
			
			$this->_repository->update($id, $brandId, $category, $name, $primaryNo, $secondaryNo);
			
			return ResponseLib::initialize()->success();
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			return ResponseLib::initialize()->fail('編輯產品料號失敗');
		}
	}
	
	/* Remove Role
	 * @params: int
	 * @return: array
	 */
	public function deleteProduct($id)
	{
		try
		{
			$this->_repository->remove($id);
			ProductRemoved::dispatch($id);
			
			return ResponseLib::initialize()->success();
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			return ResponseLib::initialize()->fail('刪除產品料號失敗');
		}
	}
	
}
