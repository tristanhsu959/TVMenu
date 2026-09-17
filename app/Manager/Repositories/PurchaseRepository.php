<?php

namespace App\Manager\Repositories;

use App\Repositories\Repository;
use App\Repositories\Traits\PurchaseReposTrait;
use App\Enums\OpCenter;
use App\Enums\Brand;
use App\Enums\Factory;
use App\Libraries\Purchase\AreaLib;
use Illuminate\Support\Facades\DB;

/* nOrder DB Common */
class PurchaseRepository extends Repository
{
	use PurchaseReposTrait;
	
	/* 取工廠清單
	 * @params: int
	 * @return: array
	 */
	public function getFactoryList($opCenter, $factoryNos)
	{
		$db = $this->connectNewOrder();
		$result = $db
			->table('Factory as f')
			->select('f.No as factoryNo', 'f.Name as factoryName')
			->whereExists(function ($query) use($opCenter) {
				$query->select(DB::raw(1))
					->from('OperationCenter as oc')
					->whereColumn('oc.Id', 'f.OperationCenterId')
					->whereIn('oc.No', $opCenter);
			})
			->whereIn('f.No', $factoryNos)
			->where('f.IsEnable', '=', 1)
			->orderBy('f.Id')
			->get()
			->toArray();
		
		return $result;
	}
	
	/******************** Product ********************/
	/* 取Product enabled setting
	 * @params: string
	 * @return: array
	 */
	public function getEnableProductSettings($brand)
	{
		$brandId = $brand->value;
		
		#取後台的enabled product
		$db = $this->connectSalesDashboard();
		$result = $db
			->table('purchase_product_setting as p')
			->select('p.purchaseBrandId as brandId', 'p.purchaseProductCode as shortCode')
			->where('p.purchaseBrandId', '=', $brandId)
			->get()
			->toArray();
		
		return $result;
	}
	
	/* 取Product id
	 * @params: int
	 * @params: string
	 * @return: array
	 */
	public function getProductIdByName($brandId, $opCenter, $name)
	{
		$db = $this->connectNewOrder();
		$result = $db
			->table('Product as a')
			->fromRaw('Product as a WITH(NOLOCK)')
			->join(DB::raw('Stocks as st WITH(NOLOCK)'), 'st.ProductId', '=', 'a.Id')
			->select('a.Id')
			->whereExists(function ($query) use($opCenter) {
				$query->select(DB::raw(1))
					->from('OperationCenter as oc')
					->whereColumn('oc.Id', 'a.OperationCenterId')
					->whereIn('oc.No', $opCenter);
			})
			/* ->whereExists(function ($query) use($brandId) {
				$query->select(DB::raw(1))
					->from('Factory as ft')
					->whereColumn('ft.Id', 'st.FactoryId')
					->whereIn('ft.No',  $this->getFactoryNo($brandId));
			}) */
			->where('a.IsStop', '=', 0)
			->where('a.Name', 'like', "%{$name}%")
			->groupBy('a.Id')
			->get()
			->pluck('Id')
			->toArray();
		
		return $result;
	}
	
	/* 取Product id - short code
	 * @params: int
	 * @params: array
	 * @return: array
	 */
	public function getProductIdByShortCode($brandId, $opCenter, $shortCodes)
	{
		$db = $this->connectNewOrder();
		$result = $db
			->table('Product as a')
			->fromRaw('Product as a WITH(NOLOCK)')
			->join(DB::raw('Stocks as st WITH(NOLOCK)'), 'st.ProductId', '=', 'a.Id')
			->select('a.Id')
			->whereExists(function ($query) use($opCenter) {
				$query->select(DB::raw(1))
					->from('OperationCenter as oc')
					->whereColumn('oc.Id', 'a.OperationCenterId')
					->whereIn('oc.No', $opCenter);
			})
			/* ->whereExists(function ($query) {
				$query->select(DB::raw(1))
					->from('Factory as ft')
					->whereColumn('ft.Id', 'st.FactoryId')
					->whereIn('ft.No',  ['TW_TP', 'TW_KH']);
			}) */
			->where('a.IsStop', '=', 0)
			->whereIn('a.OldNo', $shortCodes)
			->groupBy('a.Id')#->ddRawSql();
			->get()
			->pluck('Id')
			->toArray();
		
		return $result;
	}
	
	/* 取Product id - short code
	 * @params: int
	 * @params: array
	 * @return: array
	 */
	public function getProductShortCodeById($brandId, $opCenter, $productIds)
	{
		$db = $this->connectNewOrder();
		$result = $db
			->table('Product as a')
			->fromRaw('Product as a WITH(NOLOCK)')
			->select('a.Name as productName', 'a.OldNo as shortCode')
			->whereExists(function ($query) use($opCenter) {
				$query->select(DB::raw(1))
					->from('OperationCenter as oc')
					->whereColumn('oc.Id', 'a.OperationCenterId')
					->whereIn('oc.No', $opCenter);
			})
			->where('a.IsStop', '=', 0)
			->whereIn('a.Id', $productIds)
			->groupBy('a.Name', 'a.OldNo')#->ddRawSql();
			->get()
			->toArray();
		
		return $result;
	}
	/* 取產品分類
	 * @params: int
	 * @return: array
	 */
	public function getProductTypes($brandId)
	{
		$db = $this->connectNewOrder();
		$result = $db
			->table('ProductType as a')
			->select('a.No', 'a.Name')
			->where('a.OperationCenterId', '=', 1) #取op=1
			->where('a.IsEnable', '=', 1)
			->whereNotIn('a.No', config("web.purchase.product_type.typeNo.except.{$brandId}"))
			->groupBy('a.No', 'a.Name')
			->orderBy('a.No')
			->get()
			->toArray(); 
		
		return $result;
	}
	
	/* 取產品設定及分類
	 * @params: int
	 * @return: array
	 */
	public function getProductWithType($brandId)
	{
		$db = $this->connectNewOrder();
		$result = $db
			->table('Product as a')
			->join('ProductType as pt', 'pt.Id', '=', 'a.ProductTypeId')
			->join('Stocks as st', 'st.ProductId', '=', 'a.Id')
			->select('a.OldNo as productNo', 'a.Name as productName', 'pt.No as catNo', 'pt.Name as catName')
			->where('a.OldNo', '!=', '')
			->whereExists(function ($query) use($brandId) {
				$query->select(DB::raw(1))
					->from('OperationCenter as op')
					->whereColumn('op.Id', 'a.OperationCenterId')
					->whereIn('op.No', $this->getOpCenterNo($brandId));
			})
			->whereExists(function ($query) use($brandId) {
				$query->select(DB::raw(1))
					->from('Brand as bd')
					->whereColumn('bd.Id', 'st.BrandId')
					->where('bd.No',  $this->getBrandNo($brandId));
			})
			->whereExists(function ($query) use($brandId) {
				$query->select(DB::raw(1))
					->from('Factory as ft')
					->whereColumn('ft.Id', 'st.FactoryId')
					->whereIn('ft.No',  $this->getFactoryNo($brandId));
			})
			->whereNotIn('pt.No', config("web.purchase.product_type.typeNo.except.{$brandId}"))
			->groupBy('a.OldNo', 'a.Name', 'pt.No', 'pt.Name')
			->orderBy('pt.No')
			->orderBy('a.OldNo')
			->get()
			->toArray(); 
		
		return $result;
	}
	
	/* 取產品設定及代碼
	 * @params: int
	 * @return: array
	 */
	public function getProductAndShortCode($brand, $allowOpCenterIds)
	{
		$brandId 		= $brand->value;
		$dbBrandIds 	= $this->getDbBrandId($brand, $allowOpCenterIds);
		$enableCodes 	= config('web.purchase.product_type.shortCode.enabled');
		
		$db = $this->connectNewOrder();
		$result = $db
			->table('Stocks as a')
			->join('Product as p', 'p.Id', '=', 'a.ProductId')
			->select('p.OldNo as productNo', 'p.Name as productName')
			->where('a.ShelfStatus', '=', 1)
			->whereExists(function ($query) use($allowOpCenterIds) {
				$query->select(DB::raw(1))
					->from('OperationCenter as oc')
					->whereColumn('oc.Id', 'p.OperationCenterId')
					->whereIn('oc.No', $allowOpCenterIds);
			})
			->whereExists(function ($query) use($dbBrandIds) {
				$query->select(DB::raw(1))
					->from('Brand as bd')
					->whereColumn('bd.Id', 'a.BrandId')
					->whereIn('bd.Id',  $dbBrandIds);
			})
			#會有品牌是八方, 但出貨工廠是二崙屯山?(過濾工廠以免萬一, 不知跟調撥是否有關)
			->whereExists(function ($query) use($brandId) {
				$query->select(DB::raw(1))
					->from('Factory as ft')
					->whereColumn('ft.Id', 'a.FactoryId')
					->whereIn('ft.No',  $this->getFactoryNo($brandId));
			})
			->where('p.OldNo', '!=', '')
			->groupBy('p.OldNo', 'p.Name')
			->orderBy('p.OldNo')#->ddRawSql();
			->get()
			->toArray(); 
		
		return $result;
	}
	
	
	/******************** 供應商產品 ********************/
	/* 取供應商產品設定及代碼
	 * @params: int
	 * @return: array
	 */
	public function getSupplierProductList()
	{
		#供應商產品沒有工廠,故應不會重複
		$db = $this->connectNewOrder();
		$result = $db
			->table('SupplierProduct as p')
			->join('Supplier as s', 's.Id', '=', 'p.SupplierId')
			->select('p.Id as productId', 'p.SupplierProductNo as shortCode', 'p.SupplierProductName as productName')
			->addSelect('s.Id as supplierId', 's.Name as supplierName')
			->where('p.ShelfStatus', '=', 1)
			->get()
			->toArray(); 
		
		return $result;
	}
	
	/* 取Product id
	 * @params: int
	 * @params: string
	 * @return: array
	 */
	public function getSupplierProductIdByName($name)
	{
		$db = $this->connectNewOrder();
		
		$result = $db
			->table('SupplierProduct as p')
			->select('p.Id as productId')
			->where('p.SupplierProductName', 'like', "%{$name}%")
			->where('p.ShelfStatus', '=', 1)
			->get()
			->toArray(); 
			
		return $result;
	}
}