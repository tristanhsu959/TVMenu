<?php

namespace App\Repositories;

use App\Enums\Brand;
use App\Libraries\Sales\AreaLib;
use Illuminate\Support\Facades\DB;
use Exception;


class NewReleaseRepository extends Repository
{
	public function __construct()
	{
		
	}
	
	/* 取啟用的新品設定
	 * @params: int
	 * @return: array
	 */
	public function getNewReleaseProducts($brandId)
	{
		$db = $this->connectSalesDashboard('new_release_setting');
		$result = $db
			->select('releaseId as id', 'releaseName as name', 'releaseSaleDate as saleDate')
			->where('releaseBrandId', '=', $brandId)
			->where('releaseStatus', '=', TRUE)
			->get()
			->toArray();
		
		return $result;
	}
	
	/* 取新品設定相關條件(暫保留)
	 * @params: int
	 * @return: array
	 */
	public function getNameById($id)
	{
		$db = $this->connectSalesDashboard('new_release_setting');
		$result = $db
			->select('releaseName')
			->where('releaseId', '=', $id)
			->get()
			->first();
		
		return $result['releaseName'];
	}
	
	/* 取新品設定相關條件(暫保留)
	 * @params: int
	 * @return: array
	 */
	public function getTasteById($id)
	{
		$db = $this->connectSalesDashboard('new_release_setting');
		$result = $db
			->select('releaseTaste')
			->where('releaseId', '=', $id)
			->get()
			->first();
		
		return json_decode($result['releaseTaste'], TRUE);
	}
	
	public function getSettingById($id)
	{
		$db = $this->connectSalesDashboard('new_release_setting');
		$result = $db
			->select('releaseName', 'releaseTaste')
			->where('releaseId', '=', $id)
			->where('releaseStatus', '=', TRUE)
			->get()
			->first();
		
		if (empty($result))
			return FALSE;
		
		$result['releaseTaste'] = json_decode($result['releaseTaste'], TRUE);
		
		return $result;
	}
	
	/* 取新品設定相關條件
	 * @params: int
	 * @return: array
	 */
	public function getErpNoById($id)
	{
		$db = $this->connectSalesDashboard();
		$result = $db
			->table('new_release_product as a')
			->select('b.erpNo', 'b.isPrimary')
			->join('product_no as b', 'b.parentId', '=', 'a.productId')
			->where('a.parentId', '=', $id)
			->get()
			->toArray();
		
		return $result;
	}
	
	/* 取主資料 By records 
	 * @params: enums
	 * @params: datetime
	 * @params: datetime
	 * @params: array
	 * @params: array
	 * @params: array
	 * @params: array
	 * @return: array
	 */
	public function getSaleData($brand, $stDate, $endDate, $primaryIds, $secondaryIds, $tastes, $userAreaIds)
	{
		$primaryData 		= $this->_getPrimaryData($brand, $stDate, $endDate, $primaryIds, $tastes, $userAreaIds);
		$dualBrandedData	= $this->_getDualBrandedData($brand, $stDate, $endDate, $secondaryIds, $tastes, $userAreaIds);
		
		#合併查詢(gid在八方及御廚定義不同, 這裏不處理)
		$result = $primaryData->merge($dualBrandedData)->toArray();
		
		return $result;
	}
	
	/* Build query string | 八方,御廚
	 * @params: enums
	 * @params: datetime
	 * @params: datetime
	 * @params: array
	 * @params: array
	 * @params: array
	 * @return: array
	 */
	private function _getPrimaryData($brand, $stDate, $endDate, $erpNos, $tastes, $userAreaIds)
	{
		/* $erpNos = collect($erpNos)
			->map(fn($no) => "N'{$no}'")
			->implode(','); */
			
		$brandId = $brand->value;
		$excepts = config("web.sales.shop.except.{$brandId}");
		
		if ($brand == Brand::BAFANG)
			$db = $this->connectBFPosErp();
		else if ($brand == Brand::BUYGOOD)
			$db = $this->connectBGPosErp();
		else
			return [];
		
		$authAreaIds = AreaLib::toSalesAreaId($brand, $userAreaIds);
		
		$subQuery = $db
				->table('zs_sd_order as z')
				->fromRaw('zs_sd_order as z WITH(NOLOCK)')
				->select('z.shopId')
				->selectRaw('DATEADD(day, DATEDIFF(day, 0, z.saleDate), 0) as saleDate') #yyyy-mm-dd 00:00:00, 用cast會破壞索引
				->selectRaw('SUM(z.qty) as qty')
				->where('z.saleDate', '>=', $stDate)
				->where('z.saleDate', '<', $endDate)
				->where(function ($db) use ($erpNos, $tastes){
					#(product in (...) or taste_memo like ...)
					$db->whereIn('z.productId', $erpNos)
						->when(! empty($tastes), function ($db) use ($tastes) {
							$db->orWhereAny(['z.taste'], 'like', $tastes);
						});
				})
				->whereNotIn('z.shopId', $excepts)
				->groupByRaw('z.shopId, DATEADD(day, DATEDIFF(day, 0, z.saleDate), 0)');
				
		$result = $db
				->table(DB::raw('SHOP00 as s WITH(NOLOCK)'))
				->joinSub($subQuery, 'zs', function($join){
					$join->on('s.SHOP_ID', '=', 'zs.shopId');
				})
				->whereIn('s.gid', $authAreaIds)
				->whereNotIn('s.SHOP_ID', $excepts)
				->select('zs.shopId', 'zs.saleDate', 'zs.qty')
				->get();
		
		return $result;
	}
	
	/* Build query string | 只有御廚才有複合店才有的情境, 需去八方取御廚的資料
	 * @params: connection
	 * @params: datetime
	 * @params: datetime
	 * @params: array
	 * @params: array
	 * @params: array
	 * @return: array
	 */
	private function _getDualBrandedData($brand, $stDate, $endDate, $erpNos, $tastes, $userAreaIds)
	{
		if ($brand == Brand::BAFANG)
			return FALSE;
		
		$db = $this->connectBFPosErp();
		$authAreaIds = AreaLib::toSalesAreaId($brand, $userAreaIds);
		$dualBrandedShopIds = config('web.sales.shop.dualBrandedId');
				
		$subQuery = $db
				->table('zs_sd_order as z')
				->fromRaw('zs_sd_order as z WITH(NOLOCK)')
				->select('z.shopId')
				->selectRaw('CAST(z.saleDate AS DATE) as saleDate, sum(z.qty) as qty')
				->where('z.saleDate', '>=', $stDate)
				->where('z.saleDate', '<', $endDate)
				->where(function ($query) use ($erpNos, $tastes){
					#(product in (...) or taste_memo like ...)
					$query->whereIn('z.productId', $erpNos)
						->when(! empty($tastes), function ($query) use ($tastes) {
							$query->orWhereAny(['z.taste'], 'like', $tastes);
						});
				})
				->whereIn('z.shopId', array_keys($dualBrandedShopIds))
				->groupByRaw('z.shopId, CAST(z.saleDate AS DATE)');
				
		$result = $db
				->table(DB::raw('SHOP00 as s WITH(NOLOCK)'))
				->joinSub($subQuery, 'zs', function($join){
					$join->on('s.SHOP_ID', '=', 'zs.shopId');
				})
				->whereIn('s.gid', $authAreaIds)
				->whereIn('s.SHOP_ID', array_keys($dualBrandedShopIds))
				->select('zs.shopId', 'zs.saleDate', 'zs.qty')
				->get();
		
		#轉換shop id
		$result = $result->map(function($item, $key) use($dualBrandedShopIds) {
			$item['shopId'] = $dualBrandedShopIds[$item['shopId']];
			return $item;
		});
		
		return $result;
	}
	
	
}
