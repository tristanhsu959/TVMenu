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


class PurchaseSupplierRepository extends Repository
{
	use PurchaseReposTrait;
	
	public function __construct()
	{
		
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
			->table('SupplierOrder as a')
			->fromRaw('SupplierOrder as a WITH(NOLOCK)')
			->join(DB::raw('SupplierOrderSub as b WITH(NOLOCK)'), 'b.SupplierOrderId', '=', 'a.Id')
			->join(DB::raw('SupplierProduct as p WITH(NOLOCK)'), 'p.Id', '=', 'b.SupplierProductId')
			->join(DB::raw('Store as s WITH(NOLOCK)'), 's.Id', '=', 'a.StoreId')
			->selectRaw('CAST(DATEADD(HOUR, 8, a.ExpectedDate) AS DATE) as expectedDate')
			->addSelect('a.StoreId as storeId')
			->addSelect('p.Id as productId', 'p.SupplierProductNo as shortCode', 'p.SupplierProductName as productName')
			->addSelect('b.UnitName as unit', 'b.Quantity as qty', 'b.Money as amount')
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
			->where('b.Money', '>', 0)
			->whereIn('s.AreaId', $authAreaIds)
			->whereIn('b.SupplierProductId', $productIds)#->ddRawSql();
			->get()
			->toArray();
		
		return $result;
	}
}
