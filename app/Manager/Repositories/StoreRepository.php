<?php

namespace App\Manager\Repositories;

use App\Repositories\Repository;
use App\Enums\OpCenter;
use App\Enums\Brand;
use App\Enums\Factory;
use App\Libraries\Purchase\AreaLib;
use App\Repositories\Traits\PurchaseReposTrait;
use Illuminate\Support\Facades\DB;

/* nOrder DB Common */
class StoreRepository extends Repository
{
	use PurchaseReposTrait;
	
	/* 取門店清單(改成含蘿蔔,再由service處理)
	 * @params: array
	 * @params: enum
	 * @params: array
	 * @return: array
	 */
	public function getStoreList($brand, $allowOpCenterIds, $allowAreaIds)
	{
		#一律都取蘿蔔,再由service過濾
		$brandId = $brand->value;
		$allowAreaIds = AreaLib::toPurchaseAreaId($brand, $allowAreaIds);
		$excepts = config("web.purchase.store.except.{$brandId}", []);
		
		$dbBrandIds = $this->getDbBrandIdWithLb($brand, $allowOpCenterIds);
		
		$db = $this->connectNewOrder();
		$result = $db
			->table('Store as s')
			->join('Area as ar', 'ar.Id', '=', 's.AreaId')
			->join('StoreCar as sc', 'sc.StoreId', '=', 's.Id')
			->join('BusinessType as bt', 'bt.Id', '=', 's.BusinessTypeId')
			->join('Brand as b', 'b.Id', '=', 's.BrandId')
			->join('Factory as f', 'f.id', '=', 'sc.FactoryId')
			->select('b.No as brandNo', 'ar.Id as areaId', 'f.Name as factoryName', 's.District as district')
			->addSelect('s.Id as storeId', 's.No as storeNo', 's.Name as storeName', 's.PosId as posId', 'bt.Name as typeName')
			->selectRaw('CAST(DATEADD(HOUR, 8, s.CloseDate) AS DATE) as closeDate')
			->selectRaw('CAST(DATEADD(HOUR, 8, s.OpenDate) AS DATE) as openDate')
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
			->whereIn('s.AreaId', $allowAreaIds)
			->whereNotIn('s.No', $excepts)#->ddRawSql();
			->get()
			->toArray(); 
		
		return $result;
	}
	
	/* 取蘿蔔門店清單(Deprecated)
	 * @params: enum : 必須還是帶入八方brand
	 * @params: array
	 * @return: array
	 */
	/* public function getLbStoreList($brand, $opCenter, $userAreaIds)
	{
		$brandId = $brand->value;
		$authAreaIds = AreaLib::toPurchaseAreaId($brand, $userAreaIds);
		
		$db = $this->connectNewOrder();
		$result = $db
			->table('Store as s')
			->join('Area as ar', 'ar.Id', '=', 's.AreaId')
			->join('StoreCar as sc', 'sc.StoreId', '=', 's.Id')
			->select('ar.Id as areaId', 's.Id as storeId', 's.No as storeNo', 's.Name as storeName', 's.PosId as posId')
			->selectRaw('CAST(DATEADD(HOUR, 8, s.CloseDate) AS DATE) as closeDate')
			->selectRaw('CAST(DATEADD(HOUR, 8, s.OpenDate) AS DATE) as openDate')
			->whereExists(function ($query) use($opCenter) {
				$query->select(DB::raw(1))
					->from('OperationCenter as oc')
					->whereColumn('oc.Id', 's.OperationCenterId')
					->whereIn('oc.No', $opCenter);
			})
			->whereExists(function ($query) use($brandId) {
				$query->select(DB::raw(1))
					->from('Brand as bd')
					->whereColumn('bd.Id', 's.BrandId')
					->where('bd.No',  Brand::LUOBO->shortCode());
			})
			/* ->whereExists(function ($query) use($brandId) {
				$query->select(DB::raw(1))
					->from('Factory as ft')
					->whereColumn('ft.Id', 'sc.FactoryId')
					->whereIn('ft.No',  $this->getFactoryNo($brandId));
			}) *#/
			#->whereNull('s.CloseDate')
			->whereIn('s.AreaId', $authAreaIds)
			->whereNotIn('s.No', config("web.purchase.store.except.{$brandId}"))#->toRawSql();
			#->orderBy('s.OperationCenterId')
			#->orderBy('ar.Id')
			->get()
			->toArray(); 
		
		return $result;
	} */
	
	/* 取有效門店清單(only id:計算用)
	 * @params: enum
	 * @params: array
	 * @return: array
	 */
	public function getActiveStoreId($brand, $userAreaIds)
	{
		$brandId = $brand->value;
		$authAreaIds = AreaLib::toPurchaseAreaId($brand, $userAreaIds);
		
		$db = $this->connectNewOrder();
		$result = $db
			->table('Store as s')
			->join('Area as ar', 'ar.Id', '=', 's.AreaId')
			->join('StoreCar as sc', 'sc.StoreId', '=', 's.Id')
			->select('ar.Id as areaId', 's.Id as storeId')
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
				return $query->whereIn('s.AreaId', $authAreaIds);
			})
			->whereNotIn('s.No', config("web.purchase.store.except.{$brandId}"))
			->get()
			->toArray(); 
		
		return $result;
	}
	
	/* 取Product setting
	 * @params: string
	 * @params: string
	 * @params: string
	 * @return: array
	 */
	public function getPosIdFromEzOrder($brand)
	{
		$brandId 	= $brand->value;
		$brandCode 	= config("web.ezorder.store.code.{$brandId}"); #八方點的code
		
		$db = $this->connectQuickOrder();
		
		$result = $db
			->table(DB::raw('Stores as s WITH(NOLOCK)'))
			->select('s.storeId as storeKey', 'posid as posId')
			->where('s.brand', '=', $brandCode)
			->where('s.posid', '!=', '')
			->where('s.posid', '!=', 'null')
			->get()
			->toArray();
		
		return $result;
	}
}