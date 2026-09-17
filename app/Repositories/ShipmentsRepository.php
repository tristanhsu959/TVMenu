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


class ShipmentsRepository extends Repository
{
	use PurchaseReposTrait;
	
	public function __construct()
	{
		
	}
	
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
	
	/* 取主資料 By records 
	 * @params: enums
	 * @params: datetime
	 * @params: datetime
	 * @params: array
	 * @return: array
	 */
	public function getOrderDataByProductId($brand, $allowOpCenterIds, $allowAreaIds, $stDate, $endDate, $productIds)
	{
		#to UTC Time
		$stDate		= (new Carbon($stDate))->utc()->format('Y-m-d H:i:s');
		$endDate	= (new Carbon($endDate))->utc()->format('Y-m-d H:i:s');
		$brandId 	= $brand->value;
		$authAreaIds = AreaLib::toPurchaseAreaId($brand, $allowAreaIds);
		
		#轉成DB brandid, 其實可不判別OpCenter,結果應相同
		$dbBrandIds = $this->getDbBrandIdWithLb($brand, $allowOpCenterIds);
		
		$db = $this->connectNewOrder();
		$result = $db
			->table('Order as a')
			->fromRaw('[Order] as a WITH(NOLOCK)')
			->join(DB::raw('OrderSub as b WITH(NOLOCK)'), 'b.OrderId', '=', 'a.Id')
			#->join(DB::raw('OperationCenter as op WITH(NOLOCK)'), 'op.Id', '=', 'a.OperationCenterId')
			->join(DB::raw('Product as p WITH(NOLOCK)'), 'p.Id', '=', 'b.ProductId')
			->join(DB::raw('Store as s WITH(NOLOCK)'), 's.Id', '=', 'a.StoreId')
			->join(DB::raw('Area as ar WITH(NOLOCK)'), 'ar.Id', '=', 's.AreaId')
			->join(DB::raw('StoreCar as sc WITH(NOLOCK)'), 'sc.StoreId', '=', 'a.StoreId')
			->join(DB::raw('Factory as f WITH(NOLOCK)'), 'f.Id', '=', 'sc.FactoryId')
			->selectRaw('CAST(DATEADD(HOUR, 8, a.ExpectedDate) AS DATE) as expectedDate')
			->addSelect('ar.id as areaId', 's.Id as storeId', 's.No as storeNo')
			->addSelect('f.No as factoryNo', 'f.Name as factoryName')
			->addSelect('b.Quantity as qty', 'b.Money as amount')
			->addSelect('p.Name as productName', 'p.ErpNo as erpNo', 'p.OldNo as shortCode', 'p.Memo as memo')
			->whereExists(function ($query) use($allowOpCenterIds) {
				$query->select(DB::raw(1))
					->from('OperationCenter as oc')
					->whereColumn('oc.Id', 'a.OperationCenterId')
					->whereIn('oc.No', $allowOpCenterIds);
			})
			->whereExists(function ($query) use($dbBrandIds) {
				$query->select(DB::raw(1))
					->from('Brand as bd')
					->whereColumn('bd.Id', 's.BrandId')
					->whereIn('bd.Id',  $dbBrandIds);
			})
			->where('a.ExpectedDate', '>=', $stDate)
			->where('a.ExpectedDate', '<', $endDate)
			#->where('a.State', '=', 'functionalized')
			->where('b.Money', '>', 0)
			->where('p.ErpNo', '!=', '')
			->whereIn('s.AreaId', $authAreaIds)
			->whereIn('b.ProductId', $productIds)#->ddRawSql();
			->get()
			->toArray();
		
		return $result;
	}
	
	/* 取訂貨訂單資料 By records 
	 * @params: enums
	 * @params: datetime
	 * @params: datetime
	 * @params: array
	 * @return: array
	 */
	public function getOrderDataByStore($brand, $allowOpCenterIds, $allowAreaIds, $stDate, $endDate, $storeIds)
	{
		#to UTC Time
		$stDate			= (new Carbon($stDate))->utc()->format('Y-m-d H:i:s');
		$endDate		= (new Carbon($endDate))->utc()->format('Y-m-d H:i:s');
		$brandId 		= $brand->value;
		$authAreaIds 	= AreaLib::toPurchaseAreaId($brand, $allowAreaIds);
		
		#轉成DB brandid, 其實可不判別OpCenter,結果應相同
		$dbBrandIds = $this->getDbBrandIdWithLb($brand, $allowOpCenterIds);
		
		
		$db = $this->connectNewOrder();
		$result = $db
			->table(DB::raw('[Order] as a WITH(NOLOCK)'))
			->join(DB::raw('OrderSub as b WITH(NOLOCK)'), 'b.OrderId', '=', 'a.Id')
			->join(DB::raw('Store as s WITH(NOLOCK)'), 's.Id', '=', 'a.StoreId')
			->join(DB::raw('Product as p WITH(NOLOCK)'), 'p.Id', '=', 'b.ProductId')
			->selectRaw('CAST(DATEADD(HOUR, 8, a.ExpectedDate) AS DATE) as expectedDate')
			->addSelect('b.Quantity as qty', 'b.Money as amount')
			->addSelect('p.Name as productName', 'p.OldNo as shortCode', 's.No as storeNo')
			->whereExists(function ($query) use($allowOpCenterIds) {
				$query->select(DB::raw(1))
					->from('OperationCenter as oc')
					->whereColumn('oc.Id', 'a.OperationCenterId')
					->whereIn('oc.No', $allowOpCenterIds);
			})
			->whereExists(function ($query) use($dbBrandIds) {
				$query->select(DB::raw(1))
					->from('Brand as bd')
					->whereColumn('bd.Id', 's.BrandId')
					->whereIn('bd.Id',  $dbBrandIds);
			})
			->where('a.ExpectedDate', '>=', $stDate)
			->where('a.ExpectedDate', '<', $endDate)
			->when(! empty($storeIds), function($query) use($storeIds){
				$query->whereIn('a.StoreId', $storeIds);
			})
			->where('b.Money', '>', 0)
			->where('p.ErpNo', '!=', '')#->ddRawSql();
			->get()
			->toArray();
		
		return $result;
	}
}
