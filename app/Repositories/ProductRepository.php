<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Exception;

class ProductRepository extends Repository
{
	
	public function __construct()
	{
		
	}
	/* Case-sensitive in ubuntu */
	/* Get product list from DB 
	 * @params: 
	 * @return: array
	 */
	public function getList()
	{
		$db = $this->connectSalesDashboard('product');
			
		$result = $db
			->select('productId', 'productName', 'productBrandId', 'productCategory')
			->orderBy('productName')
			->orderBy('productCategory')
			->get()
			->toArray();
			
		return $result;
	}
	
	/* Create product
	 * @params: int
	 * @params: int
	 * @params: string
	 * @params: array
	 * @params: array
	 * @return: boolean
	 */
	public function insert($brandId, $category, $name, $primaryNo, $secondaryNo)
	{
		$db = $this->connectSalesDashboard();
		$db->beginTransaction();
		
		try 
		{
			$insertId = $this->_insertProduct($brandId, $category, $name);
			
			$isPrimary = TRUE;
			$this->_insertProductNo($insertId, $primaryNo, $isPrimary);
			
			$isPrimary = FALSE;
			$this->_insertProductNo($insertId, $secondaryNo, $isPrimary);
			
			$db->commit();

			return TRUE;
		} 
		catch (Exception $e) 
		{
			$db->rollBack();
			throw new Exception($e->getMessage());
		}
	
		return TRUE;
	}
	
	/* Create product
	 * @params: int
	 * @return: array
	 */
	private function _insertProduct($brandId, $category, $name)
	{
		$data['productBrandId']	= $brandId;
		$data['productCategory']= $category;
		$data['productName'] 	= $name;
		
		$db = $this->connectSalesDashboard();
		$insertId = $db->table('product')
			->insertGetId($data);
		
		return $insertId;
	}
	
	/* Create product no
	 * @params: int
	 * @return: array
	 */
	private function _insertProductNo($parentId, $erpNos, $isPrimary)
	{
		$items = [];
		
		foreach($erpNos as $no)
		{
			$data['parentId']	= $parentId;
			$data['erpNo'] 		= Str::trim($no);
			$data['isPrimary'] 	= $isPrimary;
			
			$items[] = $data;
		}
		
		$db = $this->connectSalesDashboard();
		$db->table('product_no')->insert($items);
		
		return TRUE;
	}
	
	/* Get product by id
	 * @params: int
	 * @return: array
	 */
	public function getById($id)
	{
		$db = $this->connectSalesDashboard('product');
			
		$result = $db->select('productId', 'productBrandId', 'productName', 'productCategory')
					->addSelect('erpNo', 'isPrimary')
					->leftJoin('product_no', 'parentId', '=', 'productId')
					->where('productId', '=', $id)
					->get();
		
		return $result;
	}
	
	/* Update product
	 * @params: int
	 * @params: string
	 * @params: int
	 * @params: string
	 * @params: string
	 * @return: boolean
	 */
	public function update($id, $brandId, $category, $name, $primaryNo, $secondaryNo)
	{
		$db = $this->connectSalesDashboard();
		$db->beginTransaction();
		
		try 
		{
			$this->_updateProduct($id, $brandId, $category, $name);
			
			$this->_removeProductNo($id);
			
			$isPrimary = TRUE;
			$this->_insertProductNo($id, $primaryNo, $isPrimary);
			
			$isPrimary = FALSE;
			$this->_insertProductNo($id, $secondaryNo, $isPrimary);
			
			$db->commit();

			return TRUE;
		} 
		catch (Exception $e) 
		{
			$db->rollBack();
			throw new Exception($e->getMessage());
		}
	
		return TRUE;
	}
	
	/* Create product
	 * @params: int
	 * @return: array
	 */
	private function _updateProduct($id, $brandId, $category, $name)
	{
		$data['productBrandId']		= $brandId;
		$data['productCategory']	= $category;
		$data['productName'] 		= $name;
		
		$db = $this->connectSalesDashboard();
		$db->table('product')
			->where('productId', '=', $id)
			->update($data);
		
		return TRUE;
	}
	
	/* Remove product
	 * @params: int
	 * @return: boolean
	 */
	public function remove($id)
	{
		$db = $this->connectSalesDashboard();
		$db->beginTransaction();
		
		try 
		{
			$this->_removeProduct($id);
			$this->_removeProductNo($id);
			$db->commit();

			return TRUE;
		} 
		catch (Exception $e) 
		{
			$db->rollBack();
			throw new Exception($e->getMessage());
		}
	
		return TRUE;
	}
	
	/* Create product no
	 * @params: int
	 * @return: array
	 */
	private function _removeProduct($id)
	{
		$db = $this->connectSalesDashboard();
		$db->table('product')
			->where('productId', '=', $id)
			->delete();
		
		return TRUE;
	}
	
	/* Create product no
	 * @params: int
	 * @return: array
	 */
	private function _removeProductNo($id)
	{
		$db = $this->connectSalesDashboard();
		$db->table('product_no')
			->where('parentId', '=', $id)
			->delete();
		
		return TRUE;
	}
}
