<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Exception;

class NewReleaseSettingRepository extends Repository
{
	
	public function __construct()
	{
		
	}
	
	/* Get product list from DB 
	 * @params: 
	 * @return: array
	 */
	public function getList()
	{
		$db = $this->connectSalesDashboard('new_release_setting');
			
		$result = $db
			->select('releaseId', 'releaseBrandId', 'releaseName', 'releaseSaleDate', 'releaseTaste', 'releaseStatus', 'updateAt')
			->get();
			
			/* ->select('releaseId', 'releaseBrandId', 'releaseName', 'releaseSaleDate', 'releaseTaste', 'releaseStatus', 'updateAt', 'productId')
			->leftJoin('new_release_product', 'parentId', '=', 'releaseId')
			->get(); */
		
		$result = $result->map(function($item, $key){
			$item['releaseTaste'] = json_decode( $item['releaseTaste'], TRUE);
			return $item;
		})->toArray();
		
		return $result;
	}
	
	/* Get product settings for options(產品料號設定)
	 * @params: 
	 * @return: array
	 */
	public function getProductSettings()
	{
		#不判別product status, 是否啟用由新品設定決定
		$db = $this->connectSalesDashboard('product');
			
		$result = $db
			->select('productId', 'productBrandId', 'productName', 'productCategory')
			->get()
			->toArray();
			
		return $result;
	}
	
	/* Create new item
	 * @params: int
	 * @params: array
	 * @params: string
	 * @params: string
	 * @params: array
	 * @params: boolean
	 * @return: array
	 */
	public function insert($brandId, $productIds, $name, $saleDate, $tastes, $status)
	{
		$db = $this->connectSalesDashboard();
		$db->beginTransaction();
		
		try 
		{
			$data['releaseBrandId']		= $brandId;
			$data['releaseName'] 		= $name;
			$data['releaseSaleDate'] 	= (new Carbon($saleDate))->format('Y-m-d');
			$data['releaseTaste'] 		= json_encode($tastes);
			$data['releaseStatus'] 		= $status;
			$data['createAt'] 			= now()->format('Y-m-d H:i:s');
			$data['updateAt'] 			= $data['createAt'];
			
			$db = $this->connectSalesDashboard();
			$insertId = $db->table('new_release_setting')->insertGetId($data);
			
			$this->_insertProducts($insertId, $productIds);
			
			$db->commit();

			return TRUE;
		} 
		catch (Exception $e) 
		{
			$db->rollBack();
			throw new Exception($e->getMessage());
		}
	}
	
	/* Insert product ids
	 * @params: int
	 * @return: array
	 */
	private function _insertProducts($parentId, $productIds)
	{
		$items = [];
		
		foreach($productIds as $id)
		{
			$data['parentId']	= $parentId;
			$data['productId']	= $id;
			
			$items[] = $data;
		}
		
		$db = $this->connectSalesDashboard();
		$db->table('new_release_product')
			->where('parentId', '=', $parentId)
			->delete();
			
		$db->table('new_release_product')->insert($items);
		
		return TRUE;
	}
	
	/* Get product by id
	 * @params: int
	 * @return: array
	 */
	public function getById($id)
	{
		$db = $this->connectSalesDashboard('new_release_setting');
			
		$result = $db->select('releaseId', 'releaseBrandId', 'releaseName', 'releaseSaleDate', 'releaseTaste', 'releaseStatus', 'updateAt', 'productId')
					->leftJoin('new_release_product', 'parentId', '=', 'releaseId')
					->where('releaseId', '=', $id)
					->get();
		$result = $result->map(function($item, $key){
			$item['releaseTaste'] = json_decode( $item['releaseTaste'], TRUE);
			return $item;
		})->toArray();
		
		return $result;
	}
	
	/* Update new item
	 * @params: int
	 * @params: int
	 * @params: int
	 * @params: string
	 * @params: string
	 * @params: array
	 * @params: boolean
	 * @return: boolean
	 */
	public function update($id, $brandId, $productIds, $name, $saleDate, $tastes, $status)
	{
		$db = $this->connectSalesDashboard();
		$db->beginTransaction();
		
		try 
		{
			$data['releaseBrandId']		= $brandId;
			$data['releaseName'] 		= $name;
			$data['releaseSaleDate'] 	= (new Carbon($saleDate))->format('Y-m-d');
			$data['releaseTaste'] 		= json_encode($tastes);
			$data['releaseStatus'] 		= $status;
			$data['updateAt'] 			= now()->format('Y-m-d H:i:s');
			
			$db = $this->connectSalesDashboard();
			$db->table('new_release_setting')
					->where('releaseId', '=', $id)
					->update($data);
					
			$this->_insertProducts($id, $productIds);
			
			$db->commit();

			return TRUE;
		} 
		catch (Exception $e) 
		{
			$db->rollBack();
			throw new Exception($e->getMessage());
		}
	}
	
	/* Remove new item
	 * @params: int
	 * @return: boolean
	 */
	public function remove($id)
	{
		$db = $this->connectSalesDashboard();
		$db->beginTransaction();
		
		try 
		{
			$db = $this->connectSalesDashboard();
			$db->table('new_release_setting')
				->where('releaseId', '=', $id)
				->delete();
			
			$db->table('new_release_product')
				->where('parentId', '=', $id)
				->delete();
					
			$db->commit();

			return TRUE;
		} 
		catch (Exception $e) 
		{
			$db->rollBack();
			throw new Exception($e->getMessage());
		}
	}
	
	/* Update status when product removed
	 * @params: int
	 * @return: array
	 */
	public function updateStatus($productId)
	{
		$db = $this->connectSalesDashboard();
		$db->reconnect(); 
		$result = $db->table('new_release_setting')
			->where('releaseProductId', '=', $productId)
			->update(['releaseStatus' => 0, 'updateAt' => now()->format('Y-m-d H:i:s')]);
		 
		return TRUE;
	}
}
