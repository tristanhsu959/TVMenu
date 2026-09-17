<?php

namespace App\Repositories;

use App\Facades\PurchaseManager;
use App\Libraries\Sales\AreaLib;
use App\Enums\OpCenter;
use App\Enums\Brand;
use App\Enums\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Exception;


class EzOrderPosRepository extends Repository
{
	public function __construct()
	{
		
	}
	
	/* 取營收資料 SALE00(sd_sale00沒有全部,故不取此table)
	 * @params: enums
	 * @params: datetime
	 * @params: datetime
	 * @params: array
	 * @params: array
	 * @return: array
	 */
	public function getDataFromPos($brand, $stDate, $endDate, $areaIds, $posIds)
	{
		$brandId = $brand->value;
		$excepts = config("web.sales.shop.except.{$brandId}");
		
		if ($brand == Brand::BAFANG)
			$db = $this->connectBFPosErp();
		else if ($brand == Brand::BUYGOOD)
			$db = $this->connectBGPosErp();
		else
			return [];
		
		$authAreaIds = AreaLib::toSalesAreaId($brand, $areaIds);
		
		$result = $db
				->table(DB::raw('SALE00 as a WITH(NOLOCK)'))
				->join(DB::raw('SHOP00 as s WITH(NOLOCK)'), 's.SHOP_ID', 'a.SHOP_ID')
				->where('a.SALE_DATE', '>=', $stDate)
				->where('a.SALE_DATE', '<', $endDate)
				->where('a.STATUS', '=', 2) #3:作廢不計入
				->when(! empty($authAreaIds), function ($query) use ($authAreaIds) {
					$query->whereIn('s.gid', $authAreaIds);
				})
				->when(! empty($posIds), function ($query) use ($posIds) {
					$query->whereIn('a.SHOP_ID', $posIds);
				})
				->whereNotIn('a.SHOP_ID', $excepts)
				->select('a.SHOP_ID as shopId')
				->selectRaw('count(a.SHOP_ID) as orderCount')
				->selectRaw('sum(a.amount) as amount')
				->selectRaw('sum(a.TOT_SALES) as totalSales')
				->selectRaw('sum(a.TOT_EXTRA) as totalExtra')
				->selectRaw('sum(a.TOT_DISCHARGE) as totalDischarge')
				->selectRaw('count(distinct CAST(a.SALE_DATE AS DATE)) as businessDays')
				->groupBy('a.SHOP_ID')#->ddRawSql();
				->get()
				->toArray();
		
		return $result; 
	}
	
	/* 只八方點時, 需從POS取營業天數
	 * @params: enums
	 * @params: datetime
	 * @params: datetime
	 * @params: array
	 * @params: array
	 * @return: array
	 */
	public function getBusinessDays($brand, $stDate, $endDate, $areaIds, $posIds)
	{
		$brandId = $brand->value;
		$excepts = config("web.sales.shop.except.{$brandId}");
		
		if ($brand == Brand::BAFANG)
			$db = $this->connectBFPosErp();
		else if ($brand == Brand::BUYGOOD)
			$db = $this->connectBGPosErp();
		else
			return [];
		
		$authAreaIds = AreaLib::toSalesAreaId($brand, $areaIds);
		
		$result = $db
				->table(DB::raw('SALE00 as a WITH(NOLOCK)'))
				->join(DB::raw('SHOP00 as s WITH(NOLOCK)'), 's.SHOP_ID', 'a.SHOP_ID')
				->where('a.SALE_DATE', '>=', $stDate)
				->where('a.SALE_DATE', '<', $endDate)
				->where('a.STATUS', '=', 2) #3:作廢不計入
				->when(! empty($authAreaIds), function ($query) use ($authAreaIds) {
					$query->whereIn('s.gid', $authAreaIds);
				})
				->when(! empty($posIds), function ($query) use ($posIds) {
					$query->whereIn('a.SHOP_ID', $posIds);
				})
				->whereNotIn('a.SHOP_ID', $excepts)
				->select('a.SHOP_ID as shopId')
				->selectRaw('count(distinct CAST(a.SALE_DATE AS DATE)) as businessDays')
				->groupBy('a.SHOP_ID')#->ddRawSql();
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
	public function getDataFromEzOrder($brand, $stDate, $endDate, $posIds)
	{
		$brandId 	= $brand->value;
		$brandCode 	= config("web.ezorder.store.code.{$brandId}"); #八方點的code
		$excepts 	= array_merge(config("web.ezorder.store.factoryStore.{$brandId}"), config("web.ezorder.store.except.{$brandId}"));
		
		$db = $this->connectQuickOrder();
		
		$result = $db
			->table(DB::raw('[Orders] as o WITH(NOLOCK)'))
			->join('Stores as s', 's.storeId', '=', 'o.storeId')
			->select('o.storeId as storeKey')
			->selectRaw('count(o.storeId) as orderCount, sum(o.price) as amount')
			->where('o.time', '>=', $stDate)
			->where('o.time', '<', $endDate)
			->where('o.isComplete', '=', 1)
			->where('o.isRefund', '=', 0)
			->where('s.brand', '=', $brandCode)
			->when(empty($posIds), function ($query) use ($excepts) {
					$query->whereNotIn('o.posid', $excepts);
			})
			->when(! empty($posIds), function ($query) use ($posIds) {
					$query->whereIn('o.posid', $posIds);
			})
			->groupBy('o.storeId')#->ddRawSql();
			->get()
			->toArray();
		
		return $result;
	}
}
