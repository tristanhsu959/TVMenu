<?php

namespace App\Repositories;

use App\Enums\Brand;
use App\Enums\Area;
use App\Libraries\Sales\AreaLib;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Exception;


class DailyRevenueRepository extends Repository
{
	public function __construct()
	{
		
	}
	
	/* 取營收資料 SALE00/有sum處理過
	 * @params: enums
	 * @params: array
	 * @params: datetime
	 * @params: datetime
	 * @params: array
	 * @params: array
	 * @return: array
	 */
	public function getSale00Data($brand, $allowAreaIds, $stDate, $endDate, $storeType, $namePosIds)
	{
		$brandId = $brand->value;
		$excepts = config("web.sales.shop.except.{$brandId}");
		
		if ($brand == Brand::BAFANG)
			$db = $this->connectBFPosErp();
		else if ($brand == Brand::BUYGOOD)
			$db = $this->connectBGPosErp();
		else if ($brand == Brand::FJVEGGIE)
			$db = $this->connectFJPosErp();
		else
			return [];
		
		$authAreaIds = AreaLib::toSalesAreaId($brand, $allowAreaIds);
		
		return $this->getFromSale00($db, $authAreaIds, $stDate, $endDate, $storeType, $namePosIds, $excepts);
		
		#endDate有先加一天
		/* $isToday = Carbon::parse($stDate)->isToday() && Carbon::parse($endDate)->subDay()->isToday();
		
		#芳珍沒有sd_sale00
		if ($isToday && $brand != Brand::FJVEGGIE)
			return $this->getFromSdSale00($db, $authAreaIds, $stDate, $endDate, $storeType, $namePosIds, $excepts);
		else
			return $this->getFromSale00($db, $authAreaIds, $stDate, $endDate, $storeType, $namePosIds, $excepts); */
	}
	
	/* 取營收資料By today only
	 * @params: enums
	 * @params: datetime
	 * @params: datetime
	 * @return: array
	 */
	public function getFromSdSale00($db, $authAreaIds, $stDate, $endDate, $storeType, $namePosIds, $excepts)
	{
		$result = $db
				->table(DB::raw('z_sd_sale00 as a WITH(NOLOCK)'))
				->join(DB::raw('SHOP00 as b WITH(NOLOCK)'), 'b.SHOP_ID', '=', 'a.shopId')
				#->join(DB::raw('shop_kind as c WITH(NOLOCK)'), 'c.sk_id', '=', 'b.SHOP_KIND')
				->where('a.saleDate', '>=', $stDate)
				->where('a.saleDate', '<', $endDate)
				->whereIn('b.SHOP_KIND', $storeType)
				->whereIn('b.gid', $authAreaIds)
				->when(! empty($namePosIds), function ($query) use ($namePosIds) {
					$query->whereIn('b.SHOP_ID', $namePosIds);
				})
				->whereNotIn('a.shopId', $excepts)
				->select('a.shopId', 'b.gid as areaId', 'a.saleDate') #'c.Sk_name as typeName', 
				->selectRaw('sum(a.amount) as amount')
				->selectRaw('sum(a.totalSales) as totalSales')
				->selectRaw('sum(a.totalExtra) as totalExtra')
				->selectRaw('sum(a.totalDischarge) as totalDischarge')
				->groupBy('a.shopId', 'b.gid', 'a.saleDate')#->ddRawSql();
				->get()
				->toArray(); 
		
		return $result;
	}
	
	/* 取營收資料 By all time range
	 * @params: enums
	 * @params: datetime
	 * @params: datetime
	 * @return: array
	 */
	public function getFromSale00($db, $authAreaIds, $stDate, $endDate, $storeType, $namePosIds, $excepts)
	{
		#Group會變超慢, 改為由PHP計算
		$subQuery = $db
				->table(DB::raw('SALE00 as a WITH(NOLOCK)'))
				->where('a.SALE_DATE', '>=', $stDate)
				->where('a.SALE_DATE', '<', $endDate)
				->where('a.STATUS', '=', 2) #3:作廢不計入
				->select('a.SALE_ID', 'a.SHOP_ID');
					
		$result = $db
				->table(DB::raw('SALE00 as a WITH(NOLOCK)'))
				->joinSub($subQuery, 'orders', function($join){
					$join->on('orders.SALE_ID', '=', 'a.SALE_ID')
						->on('orders.SHOP_ID', '=', 'a.SHOP_ID');
				})
				->join(DB::raw('SHOP00 as b WITH(NOLOCK)'), 'b.SHOP_ID', '=', 'a.SHOP_ID')
				#->join(DB::raw('shop_kind as c WITH(NOLOCK)'), 'c.sk_id', '=', 'b.SHOP_KIND')
				->whereIn('b.gid', $authAreaIds)
				->whereIn('b.SHOP_KIND', $storeType)
				->when(! empty($namePosIds), function ($query) use ($namePosIds) {
					$query->WhereIn('b.SHOP_ID', $namePosIds);
				})
				->whereNotIn('a.SHOP_ID', $excepts)
				->select('a.SHOP_ID as shopId', 'b.gid as areaId') #, 'c.Sk_name as typeName'
				->selectRaw('CAST(a.SALE_DATE AS DATE) as saleDate')
				->selectRaw('sum(a.amount) as amount')
				->selectRaw('sum(a.TOT_SALES) as totalSales')
				->selectRaw('sum(a.TOT_EXTRA) as totalExtra')
				->selectRaw('sum(a.TOT_DISCHARGE) as totalDischarge')
				->groupBy('a.SHOP_ID', 'b.gid', DB::raw('CAST(a.SALE_DATE AS DATE)'))#->ddRawSql();
				->get()
				->toArray();
		
		return $result; 
	}
	
	
	/* 取營收客單統計資料By Month
	 * @params: enums
	 * @params: datetime
	 * @params: datetime
	 * @return: array
	 */
	public function getDataByAverageOrderValue($brand, $allowAreaIds, $stDate, $endDate, $storeType)
	{
		$brandId = $brand->value;
		$excepts = config("web.sales.shop.except.{$brandId}");
		
		if ($brand == Brand::BAFANG)
			$db = $this->connectBFPosErp();
		else if ($brand == Brand::BUYGOOD)
			$db = $this->connectBGPosErp();
		else
			return [];
		
		$authAreaIds = AreaLib::toSalesAreaId($brand, $allowAreaIds);
		
		$result = $db
				->table(DB::raw('SHOP00 as a WITH(NOLOCK)'))
				->join(DB::raw('SALE00 as b WITH(NOLOCK)'), 'b.SHOP_ID', '=', 'a.SHOP_ID')
				->whereIn('a.gid', $authAreaIds)
				->whereIn('a.SHOP_KIND', $storeType)
				->whereNotIn('a.SHOP_ID', $excepts)
				->where('b.STATUS', '=', 2) #3:作廢不計入
				->where('b.SALE_DATE', '>=', $stDate)
				->where('b.SALE_DATE', '<', $endDate)
				#->select('a.SHOP_ID as shopId', 'c.Sk_name as typeName', 'a.gid as areaId')
				->select('a.SHOP_KIND as shopKind', 'a.gid as areaId')
				->selectRaw('DATEADD(month, DATEDIFF(month, 0, b.SALE_DATE), 0) as saleDate')
				->selectRaw('count(distinct a.SHOP_ID) as shopCount')
				->selectRaw('count(a.SHOP_ID) as visitors')
				->selectRaw('sum(b.amount) as amount')
				->selectRaw('sum(b.TOT_SALES) as totalSales')
				->selectRaw('sum(b.TOT_EXTRA) as totalExtra')
				->selectRaw('sum(b.TOT_DISCHARGE) as totalDischarge')
				->groupBy('a.SHOP_KIND', 'a.gid', DB::raw('DATEADD(month, DATEDIFF(month, 0, b.SALE_DATE), 0)'))#->ddRawSql();
				->get()
				->toArray();
		
		return $result; 
	}
	
	/* 取營收資料 By all time range
	 * @params: enums
	 * @params: datetime
	 * @params: datetime
	 * @return: array
	 */
	public function getFromSale00WithHourly($brand, $allowAreaIds, $stDate, $endDate, $storeType, $namePosIds)
	{
		$brandId = $brand->value;
		$excepts = config("web.sales.shop.except.{$brandId}");
		
		if ($brand == Brand::BAFANG)
			$db = $this->connectBFPosErp();
		else if ($brand == Brand::BUYGOOD)
			$db = $this->connectBGPosErp();
		else if ($brand == Brand::FJVEGGIE)
			$db = $this->connectFJPosErp();
		else
			return [];
		
		$authAreaIds = AreaLib::toSalesAreaId($brand, $allowAreaIds);
		
		$subQuery = $db
				->table(DB::raw('SALE00 as a WITH(NOLOCK)'))
				->where('a.SALE_DATE', '>=', $stDate)
				->where('a.SALE_DATE', '<', $endDate)
				->where('a.STATUS', '=', 2) #3:作廢不計入
				->select('a.SALE_ID', 'a.SHOP_ID');
					
		$result = $db
				->table(DB::raw('SALE00 as a WITH(NOLOCK)'))
				->joinSub($subQuery, 'orders', function($join){
					$join->on('orders.SALE_ID', '=', 'a.SALE_ID')
						->on('orders.SHOP_ID', '=', 'a.SHOP_ID');
				})
				->join(DB::raw('SHOP00 as b WITH(NOLOCK)'), 'b.SHOP_ID', '=', 'a.SHOP_ID')
				#->join(DB::raw('shop_kind as c WITH(NOLOCK)'), 'c.sk_id', '=', 'b.SHOP_KIND')
				->whereIn('b.gid', $authAreaIds)
				->whereIn('b.SHOP_KIND', $storeType)
				->when(! empty($namePosIds), function ($query) use ($namePosIds) {
					$query->WhereIn('b.SHOP_ID', $namePosIds);
				})
				->whereNotIn('a.SHOP_ID', $excepts)
				->select('a.SHOP_ID as shopId')
				->selectRaw('CONVERT(varchar(13), a.SALE_DATE, 120) as saleDateHour')
				->selectRaw('sum(a.amount) as amount')
				->selectRaw('sum(a.TOT_SALES) as totalSales')
				->selectRaw('sum(a.TOT_EXTRA) as totalExtra')
				->selectRaw('sum(a.TOT_DISCHARGE) as totalDischarge')
				->groupBy('a.SHOP_ID', DB::raw('CONVERT(varchar(13), a.SALE_DATE, 120)'))#->ddRawSql();
				->get()
				->toArray();
		
		return $result; 
	}
	
	/* 取營收資料 By all time range
	 * @params: enums
	 * @params: datetime
	 * @params: datetime
	 * @return: array
	 */
	public function getFromDailyClosing($brand, $allowAreaIds, $stDate, $endDate, $storeType, $namePosIds)
	{
		$brandId = $brand->value;
		$excepts = config("web.sales.shop.except.{$brandId}");
		
		if ($brand == Brand::BAFANG)
			$db = $this->connectBFPosErp();
		else if ($brand == Brand::BUYGOOD)
			$db = $this->connectBGPosErp();
		else if ($brand == Brand::FJVEGGIE)
			$db = $this->connectFJPosErp();
		else
			return [];
		
		$authAreaIds = AreaLib::toSalesAreaId($brand, $allowAreaIds);
		
		$result = $db
				->table(DB::raw('statistics_8way00 as a WITH(NOLOCK)'))
				->join(DB::raw('SHOP00 as b WITH(NOLOCK)'), 'b.SHOP_ID', '=', 'a.shop_id')
				->where('a.input_date', '>=', $stDate)
				->where('a.input_date', '<', $endDate)
				->whereIn('b.gid', $authAreaIds)
				->whereIn('b.SHOP_KIND', $storeType)
				->when(! empty($namePosIds), function ($query) use ($namePosIds) {
					$query->WhereIn('b.SHOP_ID', $namePosIds);
				})
				->whereNotIn('a.SHOP_ID', $excepts)
				->select('a.shop_id as shopId', 'a.tot_amt as amount', 'a.sale_date as saleDate')#->ddRawSql();
				->get()
				->toArray();
		
		return $result; 
	}
}
