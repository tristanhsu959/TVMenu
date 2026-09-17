<?php

namespace App\Repositories;

use App\Repositories\Traits\OrderReposTrait;
use App\Enums\Brand;
use App\Enums\Area;
use App\Libraries\Purchase\AreaLib;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Exception;


class StoreMapRepository extends Repository
{
	use OrderReposTrait;
	
	public function __construct()
	{
		
	}
	
	/* 取門店資訊, 不使用共用的trait
	 * @params: enum
	 * @params: array
	 * @return: array
	 */
	public function getStoreInfoList($brand, $userAreaIds)
	{
		$brandId = $brand->value;
		$authAreaIds = AreaLib::toPurchaseAreaId($brand, $userAreaIds);
		
		$db = $this->connectNewOrder();
		$result = $db
			->table('Store as s')
			->fromRaw('Store as s WITH(NOLOCK)')
			->join(DB::raw('Area as ar WITH(NOLOCK)'), 'ar.Id', '=', 's.AreaId')
			->join(DB::raw('StoreCar as sc WITH(NOLOCK)'), 'sc.StoreId', '=', 's.Id')
			->leftJoin(DB::raw('[User] as u WITH(NOLOCK)'), 'u.Id', '=', 's.SuperviseUserId')
			->leftJoin(DB::raw('Factory as f WITH(NOLOCK)'), 'f.Id', '=', 'sc.FactoryId')
			->leftJoin(DB::raw('Car as c WITH(NOLOCK)'), 'c.Id', '=', 'sc.CarId')
			->leftJoin(DB::raw('Warehouse as w WITH(NOLOCK)'), 'w.Id', '=', 'sc.WarehouseId')
			->select('ar.Id as areaId', 's.Id as storeId', 's.No as storeNo', 's.Name as storeName', 's.PosId as posId')
			->addSelect('s.StorePhone as storePhone', 's.Address as address', 's.VATNumber as vatNumber', 'u.Name as salesName')
			->addSelect('f.Name as factoryName', 'w.Name as warehouse', 'c.Name as carNo')
			->whereExists(function ($query) use($brandId) {
				$query->select(DB::raw(1))
					->from('OperationCenter as oc')
					->whereColumn('oc.Id', 's.OperationCenterId')
					->whereIn('oc.No', $this->getOpCenterNo($brandId));
			})
			->whereExists(function ($query) use($brandId) {
				$query->select(DB::raw(1))
					->from('Brand as bd')
					->whereColumn('bd.Id', 's.BrandId')
					->where('bd.No',  $this->getBrandNo($brandId));
			})
			->whereExists(function ($query) use($brandId) {
				$query->select(DB::raw(1))
					->from('Factory as ft')
					->whereColumn('ft.Id', 'sc.FactoryId')
					->whereIn('ft.No',  $this->getFactoryNo($brandId));
			})
			->whereNull('s.CloseDate')
			->when($authAreaIds, function ($query, $authAreaIds) {
				// 只有當 $role 為 true（或非空值）時，才會執行這裡
				return $query->whereIn('s.AreaId', $authAreaIds);
			})
			#->whereIn('s.AreaId', $authAreaIds)
			->whereNotIn('s.No', config("web.purchase.store.except.{$brandId}"))#->toRawSql();
			#->orderBy('s.OperationCenterId')
			#->orderBy('ar.Id')
			->get()
			->toArray(); 
		
		return $result;
	}
	
	/* 取店休資訊, 不使用共用的trait共
	 * @params: enum
	 * @params: array
	 * @return: array
	 */
	public function getDayoffList($brand, $stDate, $endDate, $userAreaIds)
	{
		$brandId = $brand->value;
		$authAreaIds = AreaLib::toPurchaseAreaId($brand, $userAreaIds);
		
		#To utc
		$stDate		= (new Carbon($stDate))->utc()->format('Y-m-d H:i:s');
		$endDate	= (new Carbon($endDate))->utc()->format('Y-m-d H:i:s');
		#$orderStatus = config('web.purchase.order.status.active');
		
		$db = $this->connectNewOrder();
		$result = $db
			->table('Store as s')
			->fromRaw('Store as s WITH(NOLOCK)')
			->join(DB::raw('Area as ar WITH(NOLOCK)'), 'ar.Id', '=', 's.AreaId')
			->join(DB::raw('StoreCar as sc WITH(NOLOCK)'), 'sc.StoreId', '=', 's.Id')
			->leftJoin(DB::raw('Factory as f WITH(NOLOCK)'), 'f.Id', '=', 'sc.FactoryId')
			->select('ar.Id as areaId', 's.Id as storeId', 's.No as storeNo', 's.Name as storeName', 's.PosId as posId')
			->addSelect(['money' => $db->table('Order as o')
				->select('o.Money')
				->whereColumn('o.StoreId', 's.Id')
				->where('o.ExpectedDate', '>=', $stDate)
				->where('o.ExpectedDate', '<=', $endDate)
				#->whereIn('o.State', $orderStatus)
				->limit(1)
			])
			->whereExists(function ($query) use($brandId) {
				$query->select(DB::raw(1))
					->from('OperationCenter as oc')
					->whereColumn('oc.Id', 's.OperationCenterId')
					->whereIn('oc.No', $this->getOpCenterNo($brandId));
			})
			->whereExists(function ($query) use($brandId) {
				$query->select(DB::raw(1))
					->from('Brand as bd')
					->whereColumn('bd.Id', 's.BrandId')
					->where('bd.No',  $this->getBrandNo($brandId));
			})
			->whereExists(function ($query) use($brandId) {
				$query->select(DB::raw(1))
					->from('Factory as ft')
					->whereColumn('ft.Id', 'sc.FactoryId')
					->whereIn('ft.No',  $this->getFactoryNo($brandId));
			})
			->where('s.OpenDate', '<=', $endDate) #不含未來開店
			->whereNull('s.CloseDate') #只取有效門店
			->when($authAreaIds, function ($query, $authAreaIds) {
				return $query->whereIn('s.AreaId', $authAreaIds);
			})
			->whereNotIn('s.No', config("web.purchase.store.except.{$brandId}"))#->toRawSql();
			->get()
			->toArray();
		
		return $result;
	}

}
