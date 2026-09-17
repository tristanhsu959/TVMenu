<?php

namespace App\Repositories;

use App\Enums\Brand;
use App\Enums\Area;
use App\Libraries\Sales\AreaLib;
use Illuminate\Support\Facades\DB;
use Exception;
use Log;

class SaleEventsRepository extends Repository
{
	public function __construct()
	{
	}
	
	/* Sale data 
	 * @params: query builder
	 * @params: datetime
	 * @params: datetime
	 * @params: array
	 * @params: string
	 * @params: array
	 * @return: array
	 */
	public function getMoonFestivalSaleData($brand, $allowAreaIds, $stDate, $endDate, $comboId, $productIds)
	{
		if ($brand == Brand::BAFANG)
			$db = $this->connectBFPosErp();
		else
			return [];
		
		$subQuery = $this->_getMoonFestivalSubQuery($db, $brand, $allowAreaIds, $stDate, $endDate, $comboId);
		
		$result = $db
				->table('SALE01 as a')
				->fromRaw('SALE01 as a WITH(NOLOCK)')
				->join(DB::raw('SALE00 as b WITH(NOLOCK)'), function($query){
					$query->on('b.SHOP_ID', '=', 'a.SHOP_ID')
							->on('b.SALE_ID', '=', 'a.SALE_ID');
				})
				->joinSub($subQuery, 'combo', function ($join) {
					$join->on('a.SHOP_ID', '=', 'combo.SHOP_ID')
						 ->on('a.SALE_ID', '=', 'combo.SALE_ID');
				})
				->select('a.SHOP_ID as shopId', 'a.PROD_ID as erpNo')
				->selectRaw('sum(a.QTY) as qty')
				->selectRaw('CAST(combo.SALE_DATE AS DATE) as saleDate')
				->where('b.SALE_DATE', '>=', $stDate)
				->where('b.SALE_DATE', '<', $endDate)
				->where('a.COMB_TYPE', '=', 2)
				->whereColumn('a.COMB_SALE_SNO', '=', 'combo.SALE_SNO')
				->groupByRaw('a.SHOP_ID, a.PROD_ID')
				->groupByRaw('CAST(combo.SALE_DATE AS DATE)')
				->get()
				->toArray(); 
		
		return $result;
	}
	
	private function _getMoonFestivalSubQuery($db, $brand, $allowAreaIds, $stDate, $endDate, $comboId)
	{
		$authAreaIds = AreaLib::toSalesAreaId($brand, $allowAreaIds);
		
		#因會無法跑index, sum由PHP計算
		$result = $db
				->table('SALE01 as a')
				->fromRaw('SALE01 as a WITH(NOLOCK)')
				->join(DB::raw('SHOP00 as s WITH(NOLOCK)'), 's.SHOP_ID', '=', 'a.SHOP_ID')
				->join(DB::raw('SALE00 as b WITH(NOLOCK)'), function($query){
					$query->on('b.SHOP_ID', '=', 'a.SHOP_ID')
							->on('b.SALE_ID', '=', 'a.SALE_ID');
				})
				->select('a.SHOP_ID', 'a.SALE_ID', 'a.SALE_SNO', 'b.SALE_DATE')
				->where('b.SALE_DATE', '>=', $stDate)
				->where('b.SALE_DATE', '<', $endDate)
				->whereIn('s.gid', $authAreaIds)
				->where('a.PROD_ID', $comboId);
		
		return $result;
	}
}
