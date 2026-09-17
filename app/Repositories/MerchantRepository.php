<?php

namespace App\Repositories;

use App\Facades\PurchaseManager;
use App\Enums\Brand;
use App\Enums\Area;
use App\Libraries\Purchase\AreaLib;
use App\Repositories\Traits\PurchaseReposTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Exception;


class MerchantRepository extends Repository
{
	use PurchaseReposTrait;
	
	public function __construct()
	{
		
	}
	
	/* 取門店資訊, 不使用共用的trait
	 * @params: enum
	 * @params: array
	 * @return: array
	 */
	public function getStoreInfoList($brand, $allowOpCenterIds, $allowAreaIds)
	{
		$brandId = $brand->value;
		$allowAreaIds = AreaLib::toPurchaseAreaId($brand, $allowAreaIds);
		$excepts = config("web.purchase.store.except.{$brandId}");
		
		$dbBrandIds = $this->getDbBrandIdWithLb($brand, $allowOpCenterIds);
		
		$db = $this->connectNewOrder();
		$result = $db
			->table('Store as s')
			->fromRaw('Store as s WITH(NOLOCK)')
			->join(DB::raw('Brand as b WITH(NOLOCK)'), 'b.Id', '=', 's.BrandId')
			->join(DB::raw('Area as ar WITH(NOLOCK)'), 'ar.Id', '=', 's.AreaId')
			->join(DB::raw('StoreCar as sc WITH(NOLOCK)'), 'sc.StoreId', '=', 's.Id')
			#->leftJoin(DB::raw('[User] as u WITH(NOLOCK)'), 'u.Id', '=', 's.SuperviseUserId')
			->leftJoin(DB::raw('Factory as f WITH(NOLOCK)'), 'f.Id', '=', 'sc.FactoryId')
			->leftJoin(DB::raw('Car as c WITH(NOLOCK)'), 'c.Id', '=', 'sc.CarId')
			->leftJoin(DB::raw('Warehouse as w WITH(NOLOCK)'), 'w.Id', '=', 'sc.WarehouseId')
			->select('b.No as brandNo', 'ar.Id as areaId', 's.Id as storeId', 's.No as storeNo', 's.Name as storeName', 's.PosId as posId')
			->addSelect('s.StorePhone as storePhone', 's.Address as address', 's.VATNumber as vatNumber')
			->addSelect('f.Name as factoryName', 'w.Name as warehouse', 'c.Name as carNo')
			->whereExists(function ($query) use($allowOpCenterIds) {
				$query->select(DB::raw(1))
					->from('OperationCenter as oc')
					->whereColumn('oc.Id', 's.OperationCenterId')
					->whereIn('oc.No', $allowOpCenterIds);
			})
			->whereExists(function ($query) use($dbBrandIds) {
				$query->select(DB::raw(1))
					->from('Brand as bd')
					->whereColumn('bd.Id', 's.BrandId')
					->whereIn('bd.Id',  $dbBrandIds);
			})
			->whereNull('s.CloseDate')
			->when($allowAreaIds, function ($query, $allowAreaIds) {
				// 只有當 $role 為 true（或非空值）時，才會執行這裡
				return $query->whereIn('s.AreaId', $allowAreaIds);
			})
			->whereNotIn('s.No', $excepts)#->ddRawSql();
			->get()
			->toArray();
		
		return $result;
	}
	
	/* get area manager
	 * @params: enum
	 * @params: array
	 * @return: array
	 */
	public function getAreaManagerList()
	{
		$db = $this->connectSalesDashboard();
		
		$result = $db->table('area_manager_mapping')->get();
		
		return $result;
	}
	
	/* 取店休資訊, 不使用共用的trait
	 * @params: enum
	 * @params: array
	 * @return: array
	 */
	public function getDayoffList($brand, $allowOpCenterIds, $allowAreaIds, $stDate, $endDate, $productIds)
	{
		$brandId 	= $brand->value;
		#To utc
		$stDate		= (new Carbon($stDate))->utc()->format('Y-m-d H:i:s');
		$endDate	= (new Carbon($endDate))->utc()->format('Y-m-d H:i:s');
		
		$authAreaIds	= AreaLib::toPurchaseAreaId($brand, $allowAreaIds);
		#有判別brand不會抓到蘿蔔店,故要先處理
		$dbBrandIds 	= $this->getDbBrandIdWithLb($brand, $allowOpCenterIds); #有LB
		
		$excepts = config("web.purchase.store.except.{$brandId}");
		
		$db = $this->connectNewOrder();
		$result = $db
			->table('Store as s')
			->fromRaw('Store as s WITH(NOLOCK)')
			->join(DB::raw('[Order] as o WITH(NOLOCK)'), 'o.StoreId', '=', 's.Id')
			->join(DB::raw('OrderSub as os WITH(NOLOCK)'), 'os.OrderId', '=', 'o.Id')
			->join(DB::raw('Product as p WITH(NOLOCK)'), 'p.Id', '=', 'os.ProductId')
			->select('s.No as storeNo', 'p.OldNo as shortCode') #, 'p.Name as productName'
			->selectRaw('sum(os.quantity) as qty')
			->whereExists(function ($query) use($allowOpCenterIds) {
				$query->select(DB::raw(1))
					->from('OperationCenter as oc')
					->whereColumn('oc.Id', 's.OperationCenterId')
					->whereIn('oc.No', $allowOpCenterIds);
			})
			->whereExists(function ($query) use($dbBrandIds) {
				$query->select(DB::raw(1))
					->from('Brand as bd')
					->whereColumn('bd.Id', 's.BrandId')
					->whereIn('bd.Id',  $dbBrandIds);
			})
			->where('s.OpenDate', '<=', $endDate) #不含未來開店
			->whereNull('s.CloseDate') #只取有效門店
			->when($authAreaIds, function ($query, $authAreaIds) {
				return $query->whereIn('s.AreaId', $authAreaIds);
			})
			->whereIn('os.ProductId', $productIds)
			->where('o.ExpectedDate', '>=', $stDate)
			->where('o.ExpectedDate', '<', $endDate)
			->whereNotIn('s.No', $excepts)
			->groupBy('s.No', 'p.OldNo')#->toRawSql();
			->get()
			->toArray();
		
		return $result;
	}
	
	/* public function getDayoffList($brand, $allowOpCenterIds, $allowAreaIds, $stDate, $endDate, $productIds)
	{
		$brandId 	= $brand->value;
		#To utc
		$stDate		= (new Carbon($stDate))->utc()->format('Y-m-d H:i:s');
		$endDate	= (new Carbon($endDate))->utc()->format('Y-m-d H:i:s');
		
		$authAreaIds	= AreaLib::toPurchaseAreaId($brand, $allowAreaIds);
		#有判別brand不會抓到蘿蔔店,故要先處理
		$dbBrandIds 	= $this->getDbBrandIdWithLb($brand, $allowOpCenterIds); #有LB
		
		$excepts = config("web.purchase.store.except.{$brandId}");
		
		$db = $this->connectNewOrder();
		$result = $db
			->table('Store as s')
			->fromRaw('Store as s WITH(NOLOCK)')
			->select('s.No as storeNo')
			->addSelect(['money' => $db->table('Order as o')
				->select('o.Money')
				->whereColumn('o.StoreId', 's.Id')
				->where('o.ExpectedDate', '>=', $stDate)
				->where('o.ExpectedDate', '<', $endDate)
				#->whereIn('o.State', $orderStatus)
				->limit(1)
			])
			->whereExists(function ($query) use($allowOpCenterIds) {
				$query->select(DB::raw(1))
					->from('OperationCenter as oc')
					->whereColumn('oc.Id', 's.OperationCenterId')
					->whereIn('oc.No', $allowOpCenterIds);
			})
			->whereExists(function ($query) use($dbBrandIds) {
				$query->select(DB::raw(1))
					->from('Brand as bd')
					->whereColumn('bd.Id', 's.BrandId')
					->whereIn('bd.Id',  $dbBrandIds);
			})
			->where('s.OpenDate', '<=', $endDate) #不含未來開店
			->whereNull('s.CloseDate') #只取有效門店
			->when($authAreaIds, function ($query, $authAreaIds) {
				return $query->whereIn('s.AreaId', $authAreaIds);
			})
			->whereNotIn('s.No', $excepts)->toRawSql();
			/* ->get()
			->toArray(); 
		
		return $result;
	} */

}
