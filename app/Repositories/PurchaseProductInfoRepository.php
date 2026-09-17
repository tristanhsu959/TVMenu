<?php

namespace App\Repositories;

use App\Repositories\Traits\PurchaseReposTrait;
use App\Facades\PurchaseManager;
use App\Libraries\Purchase\AreaLib;
use App\Enums\OpCenter;
use App\Enums\Brand;
use App\Enums\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Exception;


class PurchaseProductInfoRepository extends Repository
{
	use PurchaseReposTrait;
	
	public function __construct()
	{
		
	}
	
	/* 一般產品
	 * @params: enums
	 * @params: array
	 * @params: array
	 * @params: boolean
	 * @params: array
	 * @return: array
	 */
	public function getProductList($brand, $allowOpCenterIds, $factoryIds, $hasOffShelf, $productIds)
	{
		#to UTC Time
		$brandId 	= $brand->value;
		
		#轉成DB brandid, 其實可不判別OpCenter,結果應相同
		$dbBrandIds = $this->getDbBrandId($brand, $allowOpCenterIds);
		
		$db = $this->connectNewOrder();
		$result = $db
			->table('Product as p')
			->fromRaw('[Product] as p WITH(NOLOCK)')
			->join(DB::raw('OperationCenter as o WITH(NOLOCK)'), 'o.Id', '=', 'p.OperationCenterId')
			->join(DB::raw('Stocks as s WITH(NOLOCK)'), 's.ProductId', '=', 'p.Id')
			->join(DB::raw('Factory as f WITH(NOLOCK)'), 'f.Id', '=', 's.FactoryId')
			->join(DB::raw('Warehouse as w WITH(NOLOCK)'), 'w.Id', '=', 's.WarehouseId')
			->join(DB::raw('Unit as u WITH(NOLOCK)'), 'u.Id', '=', 'p.UnitId')
			->select('p.Name as productName', 'p.ErpNo as erpNo', 'p.OldNo as shortCode', 'p.Memo as memo', 'p.Price as price')
			->addSelect('f.No as factoryNo', 'f.Name as factoryName', 'w.No as warehouseNo', 'w.Name as warehouse')
			->addSelect('u.Name as unit', 's.ShelfStatus as status', 'o.Name as opCenter')
			->whereExists(function ($query) use($allowOpCenterIds) {
				$query->select(DB::raw(1))
					->from('OperationCenter as oc')
					->whereColumn('oc.Id', 'p.OperationCenterId')
					->whereIn('oc.No', $allowOpCenterIds);
			})
			->whereExists(function ($query) use($dbBrandIds) {
				$query->select(DB::raw(1))
					->from('Brand as bd')
					->whereColumn('bd.Id', 's.BrandId')
					->whereIn('bd.Id',  $dbBrandIds);
			})
			->when(! empty($factoryIds), function ($query) use($factoryIds) {
				$query->whereIn('f.No',  $factoryIds);
			})
			->when(! $hasOffShelf, function ($query) { #false只取上架
				$query->where('s.ShelfStatus', '=', 1);
			})
			->when(! empty($productIds), function ($query) use($productIds) {
				$query->whereIn('p.Id',  $productIds);
			})
			->where('p.ErpNo', '!=', '')#->ddRawSql();
			->orderBy('f.OperationCenterId')
			->get()
			->toArray();
		
		return $result;
	}
	
	/* 預購產品
	 * @params: enums
	 * @params: array
	 * @params: array
	 * @params: boolean
	 * @return: array
	 */
	public function getPreOrderProductList($brand, $allowOpCenterIds, $hasOffShelf)
	{
		#to UTC Time
		$brandId 	= $brand->value;
		
		#轉成DB brandid, 其實可不判別OpCenter,結果應相同
		$dbBrandIds = $this->getDbBrandId($brand, $allowOpCenterIds);
		
		#預購似乎只能分到品牌, 無法分工廠(查出結果有異狀)
		$db = $this->connectNewOrder();
		$result = $db
			->table('PreorderProducts as pp')
			->fromRaw('PreorderProducts as pp WITH(NOLOCK)')
			->join(DB::raw('OperationCenter as o WITH(NOLOCK)'), 'o.Id', '=', 'pp.OperationCenterId')
			->join(DB::raw('Product as p WITH(NOLOCK)'), 'p.Id', '=', 'pp.ProductId')
			->join(DB::raw('Stocks as s WITH(NOLOCK)'), 's.ProductId', '=', 'pp.ProductId')
			#->join(DB::raw('Factory as f WITH(NOLOCK)'), 'f.Id', '=', 's.FactoryId')
			->join(DB::raw('Unit as u WITH(NOLOCK)'), 'u.Id', '=', 'p.UnitId')
			->select('pp.ProductId as productId', 'pp.PreName as productName', 'pp.OldNo as shortCode', 'pp.ProductNote as memo', 'pp.IsEnable as status')
			#->addSelect('f.No as factoryNo', 'f.Name as factoryName')
			->addSelect('u.Name as unit', 'o.Name as opCenter')
			->whereExists(function ($query) use($allowOpCenterIds) {
				$query->select(DB::raw(1))
					->from('OperationCenter as oc')
					->whereColumn('oc.Id', 'pp.OperationCenterId')
					->whereIn('oc.No', $allowOpCenterIds);
			})
			->whereExists(function ($query) use($dbBrandIds) {
				$query->select(DB::raw(1))
					->from('Brand as bd')
					->whereColumn('bd.Id', 's.BrandId')
					->whereIn('bd.Id',  $dbBrandIds);
			})
			/* ->when(! empty($factoryIds), function ($query) use($factoryIds) {
				$query->whereIn('f.No',  $factoryIds);
			}) */
			->when(! $hasOffShelf, function ($query) { #false只取上架
				$query->where('pp.IsEnable',  1);
			})
			->orderBy('pp.OperationCenterId')#->ddRawSql();
			->get()
			->toArray();
		
		return $result;
	}
	
	/* 供應商產品
	 * @params: enums
	 * @params: array
	 * @params: boolean
	 * @return: array
	 */
	public function getSupplierProductList($brand, $allowOpCenterIds, $factoryIds, $hasOffShelf)
	{
		#to UTC Time
		$brandId = $brand->value;
		
		#因無brand可判別, 這裏要用工廠來判別, 且一定要給值
		$allFactoryIds = $this->getFactoryNo($brandId);
		$allowFactoryIds = empty($factoryIds) ? $allFactoryIds : $factoryIds;
		
		$db = $this->connectNewOrder();
		$result = $db
			->table('SupplierProduct as sp')
			->fromRaw('SupplierProduct as sp WITH(NOLOCK)')
			->join(DB::raw('OperationCenter as o WITH(NOLOCK)'), 'o.Id', '=', 'sp.OperationCenterId')
			->join(DB::raw('Supplier as s WITH(NOLOCK)'), 's.Id', '=', 'sp.SupplierId')
			->join(DB::raw('SupplierFactory as sf WITH(NOLOCK)'), 'sf.SupplierId', '=', 'sp.SupplierId')
			->join(DB::raw('Factory as f WITH(NOLOCK)'), 'f.Id', '=', 'sf.FactoryId')
			->join(DB::raw('Unit as u WITH(NOLOCK)'), 'u.Id', '=', 'sp.UnitId')
			->select('sp.SupplierProductName as productName', 'sp.ErpNo as erpNo', 'sp.SupplierProductNo as shortCode', 'sp.ProductNote as memo')
			->addSelect('sp.Price as price', 'sp.PurchasePrice as purchasePrice', 'sp.ShelfStatus as status')
			->addSelect('s.No as supplierNo', 's.Name as supplierName')
			->addSelect('f.No as factoryNo', 'f.Name as factoryName')
			->addSelect('u.Name as unit', 'o.Name as opCenter')
			->whereExists(function ($query) use($allowOpCenterIds) {
				$query->select(DB::raw(1))
					->from('OperationCenter as oc')
					->whereColumn('oc.Id', 'sp.OperationCenterId')
					->whereIn('oc.No', $allowOpCenterIds);
			})
			->whereIn('f.No',  $allowFactoryIds)
			->when(! $hasOffShelf, function ($query) { #false只取上架
				$query->where('sp.ShelfStatus', '=', 1);
			})
			->orderBy('sp.OperationCenterId')#->ddRawSql();
			->get()
			->toArray();
		
		return $result;
	}
	
}
