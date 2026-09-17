<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Exception;

class SalesProductRepository extends Repository
{
	public function __construct()
	{
		
	}
	
	/* Get product setting from DB 
	 * @params: 
	 * @return: array
	 */
	public function getSetting()
	{
		$db = $this->connectSalesDashboard();
			
		$result = $db
			->table('sales_product_setting as s')
			->select('s.salesBrandId as brandId', 's.salesProductId as productId')
			->addSelect('p.productName', 'p.productCategory as category')
			->join('product as p', 'p.productId', '=', 's.salesProductId')
			->get()
			->toArray();
			
		return $result;
	}
	
	/* Get product list
	 * @params: 
	 * @return: array
	 */
	public function getErpProductList()
	{
		$db = $this->connectSalesDashboard();
			
		$result = $db
			->table('product as p')
			->select('p.productBrandId as brandId', 'p.productId as id', 'p.productName as name', 'p.productCategory as categoryId')
			->get()
			->toArray();
			
		return $result;
	}
	
	/* Create new item
	 * @params: int
	 * @params: array
	 * @return: array
	 */
	public function update($productIds)
	{
		$db = $this->connectSalesDashboard();
		$db->beginTransaction();
		
		try 
		{
			$this->_removeProduct();
			$this->_insertProduct($productIds);
			
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
	private function _insertProduct($productIds)
	{
		$items = [];
		
		foreach($productIds as $brandId => $products)
		{
			foreach($products as $id)
			{
				$data['salesBrandId']	= $brandId;
				$data['salesProductId']	= $id;
				
				$items[] = $data;
			}
		}
		
		$db = $this->connectSalesDashboard();
		$db->table('sales_product_setting')
			->insert($items);
		
		return TRUE;
	}
	
	/* Remove item
	 * @params: int
	 * @return: boolean
	 */
	public function _removeProduct()
	{
		$db = $this->connectSalesDashboard();
		$db->table('sales_product_setting')
			->delete();
			
		return TRUE;
	}
	
	/* Update status invoke by product setting
	 * @params: int
	 * @return: boolean
	 */
	public function updateStatus($productId)
	{
		$db = $this->connectSalesDashboard();
		$db->table('sales_product_setting')
			->where('salesProductId', '=', $productId)
			->delete();
			
		return TRUE;
	}
	
}
