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


class AreaManagerRepository extends Repository
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
	public function getStoreList($brand, $allowOpCenterIds, $allowAreaIds)
	{
		$brandId = $brand->value;
		$allowAreaIds = AreaLib::toPurchaseAreaId($brand, $allowAreaIds);
		$excepts = config("web.purchase.store.except.{$brandId}");
		
		$dbBrandIds = $this->getDbBrandId($brand, $allowOpCenterIds);
		
		$db = $this->connectNewOrder();
		$result = $db
			->table('Store as s')
			->fromRaw('Store as s WITH(NOLOCK)')
			->join(DB::raw('Brand as b WITH(NOLOCK)'), 'b.Id', '=', 's.BrandId')
			->join(DB::raw('Area as ar WITH(NOLOCK)'), 'ar.Id', '=', 's.AreaId')
			->join(DB::raw('StoreCar as sc WITH(NOLOCK)'), 'sc.StoreId', '=', 's.Id')
			->select('ar.Id as areaId', 's.No as storeNo', 's.Name as storeName', 's.PosId as posId')
			->addSelect('s.StorePhone as storePhone', 's.Address as address', 's.VATNumber as vatNumber')
			->addSelect('s.BossName as bossName', 's.BossPhone as bossPhone', 's.CloseDate as closeDate')
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
	public function getAreaManagerMapping()
	{
		$db = $this->connectSalesDashboard();
		
		$result = $db->table('area_manager_mapping')->get();
		
		return $result;
	}
	
	/* Update area manager
	 * @params: enum
	 * @params: array
	 * @return: array
	 */
	public function updateAreaManager($data)
	{
		$db = $this->connectSalesDashboard();
		
		$result = $db->table('area_manager_mapping')->upsert(
			$data,
			['storeKey'],
			['areaManager']
		);
		
		return $result;
	}
}
