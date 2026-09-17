<?php

namespace App\Repositories;

use App\Enums\Brand;
use App\Enums\Area;
use App\Libraries\Sales\AreaLib;
use Illuminate\Support\Facades\DB;
use Exception;
use Log;

class SalesRepository extends Repository
{
	public function __construct()
	{
	}
	
	/* 取產品設定相關條件-查詢條件Options
	 * @params: int
	 * @return: array
	 */
	public function getEnableProducts($brandId)
	{
		$db = $this->connectSalesDashboard();
		$result = $db
			->table('sales_product_setting as s')
			->join('product as p', 'p.productId', '=', 's.salesProductId')
			->select('s.salesProductId as productId', 'p.productName', 'p.productCategory as categoryId')
			->where('s.salesBrandId', '=', $brandId)
			->get()
			->toArray();
		
		return $result;
	}
	
	/* 取查詢Dashboard產品對應的erp no
	 * @params: int
	 * @return: array
	 */
	public function getProductByIds($productIds)
	{
		#product id是唯一的, 故可不需要brand id
		$db = $this->connectSalesDashboard();
		$result = $db
			->table('product as p')
			->select('p.productId', 'p.productBrandId', 'p.productName', 'pn.erpNo', 'pn.isPrimary')
			->join('product_no as pn', 'pn.parentId', '=', 'p.productId')
			->whereIn('p.productId', $productIds)
			->get()
			->toArray();
		
		return $result;
	}
	
	/* Sale data | 新品:八方/梁社漢共用
	 * @params: query builder
	 * @params: datetime
	 * @params: datetime
	 * @params: array
	 * @params: string
	 * @params: array
	 * @return: array
	 */
	public function getSaleData($brand, $userAreaIds, $stDate, $endDate, $primaryIds, $secondaryIds, $storeIds = [])
	{
		$primaryData 		= $this->_getPrimaryData($brand, $userAreaIds, $stDate, $endDate, $primaryIds, $storeIds);
		$dualBrandedData	= $this->_getDualBrandedData($brand, $userAreaIds, $stDate, $endDate, $secondaryIds, $storeIds);
		
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
	private function _getPrimaryData($brand, $userAreaIds, $stDate, $endDate, $erpNos, $storeIds)
	{
		if ($brand == Brand::BAFANG)
			$db = $this->connectBFPosErp();
		else if ($brand == Brand::BUYGOOD)
			$db = $this->connectBGPosErp();
		else
			return [];
		
		$authAreaIds = AreaLib::toSalesAreaId($brand, $userAreaIds);
		
		#因會無法跑index, sum由PHP計算
		$result = $db
				->table('zs_sd_order as a')
				->fromRaw('zs_sd_order as a WITH(NOLOCK)')
				->join(DB::raw('SHOP00 as s WITH(NOLOCK)'), 's.SHOP_ID', '=', 'a.shopId')
				->select('a.shopId', 'a.productId as erpNo')
				->selectRaw('sum(a.price * a.qty + a.discount) as amount')
				->selectRaw('sum(a.qty) as qty')
				->selectRaw('CAST(a.saleDate AS DATE) as saleDate')
				->where('a.saleDate', '>=', $stDate)
				->where('a.saleDate', '<', $endDate)
				->whereIn('s.gid', $authAreaIds)
				->whereIn('a.productId', $erpNos)
				->when(! empty($storeIds), function($query) use($storeIds){
					$query->whereIn('a.shopId', $storeIds);
				})
				#->whereNotIn('s.SHOP_ID', $excepts) 由PHP過濾
				->groupByRaw('a.shopId, a.productId')
				->groupByRaw('CAST(a.saleDate AS DATE)')
				->get(); 
		
		return $result;
	}
	
	/* Build query string | 八方:只有複合店才有的情境
	 * @params: connection
	 * @params: datetime
	 * @params: datetime
	 * @params: array
	 * @params: array
	 * @params: array
	 * @return: array
	 */
	private function _getDualBrandedData($brand, $userAreaIds, $stDate, $endDate, $erpNos, $storeIds)
	{
		if ($brand == Brand::BAFANG)
			return [];
		
		$db = $this->connectBFPosErp();
		$authAreaIds = AreaLib::toSalesAreaId($brand, $userAreaIds);
		$dualBrandedShopIds = config('web.sales.shop.dualBrandedId');
		
		$result = $db
				->table('zs_sd_order as a')
				->fromRaw('zs_sd_order as a WITH(NOLOCK)')
				->join(DB::raw('SHOP00 as s WITH(NOLOCK)'), 's.SHOP_ID', '=', 'a.shopId')
				->select('a.shopId', 'a.productId as erpNo')
				->selectRaw('sum(a.price * a.qty + a.discount) as price_sum')
				->selectRaw('sum(a.qty) as qty_sum')
				->selectRaw('CAST(a.saleDate AS DATE) as saleDate')
				->where('a.saleDate', '>=', $stDate)
				->where('a.saleDate', '<', $endDate)
				->whereIn('s.gid', $authAreaIds)
				->whereIn('a.productId', $erpNos)
				->whereIn('a.shopId', array_keys($dualBrandedShopIds))
				->when(! empty($storeIds), function($query) use($storeIds){
					$query->whereIn('a.shopId', $storeIds);
				})
				->groupByRaw('a.shopId, a.productId')
				->groupByRaw('CAST(a.saleDate AS DATE)')
				->get();
		
		#轉換shop id
		$result = $result->map(function($item, $key) use($dualBrandedShopIds) {
			$item['shopId'] = $dualBrandedShopIds[$item['shopId']];
			return $item;
		});
		
		return $result;
	}
	
	/* Sale data | 新品:八方/梁社漢共用
	 * @params: query builder
	 * @params: datetime
	 * @params: datetime
	 * @params: array
	 * @params: string
	 * @params: array
	 * @return: array
	 */
	public function getSaleDataByStore($brand, $userAreaIds, $stDate, $endDate, $primaryIds, $secondaryIds, $storeIds)
	{
		$primaryData 		= $this->_getPrimaryDataByStore($brand, $userAreaIds, $stDate, $endDate, $primaryIds, $storeIds);
		$dualBrandedData	= $this->_getDualBrandedDataByStore($brand, $userAreaIds, $stDate, $endDate, $secondaryIds, $storeIds);
		
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
	private function _getPrimaryDataByStore($brand, $userAreaIds, $stDate, $endDate, $erpNos, $storeIds)
	{
		if ($brand == Brand::BAFANG)
			$db = $this->connectBFPosErp();
		else if ($brand == Brand::BUYGOOD)
			$db = $this->connectBGPosErp();
		else
			return [];
		
		$authAreaIds = AreaLib::toSalesAreaId($brand, $userAreaIds);
		
		#因會無法跑index, sum由PHP計算
		$result = $db
				->table('zs_sd_order as a')
				->fromRaw('zs_sd_order as a WITH(NOLOCK)')
				->join(DB::raw('SHOP00 as s WITH(NOLOCK)'), 's.SHOP_ID', '=', 'a.shopId')
				->select('a.shopId', 'a.productId as erpNo', 'a.price', 'a.qty', 'a.discount')
				#->select('a.shopId', 'a.productId as erpNo')
				#->selectRaw('sum(a.price * a.qty + a.discount) as price_sum')
				#->selectRaw('sum(a.qty) as qty_sum')
				->where('a.saleDate', '>=', $stDate)
				->where('a.saleDate', '<', $endDate)
				->whereIn('s.gid', $authAreaIds)
				->whereIn('a.productId', $erpNos)
				#->whereNotIn('s.SHOP_ID', $excepts) 由PHP過濾
				#->groupByRaw('a.shopId, a.productId')
				->get(); 
				
		return $result;
	}
	
	/* Build query string | 八方:只有複合店才有的情境
	 * @params: connection
	 * @params: datetime
	 * @params: datetime
	 * @params: array
	 * @params: array
	 * @params: array
	 * @return: array
	 */
	private function _getDualBrandedDataByStore($brand, $userAreaIds, $stDate, $endDate, $erpNos, $storeIds)
	{
		if ($brand == Brand::BAFANG)
			return [];
		
		$db = $this->connectBFPosErp();
		$authAreaIds = AreaLib::toSalesAreaId($brand, $userAreaIds);
		$dualBrandedShopIds = config('web.sales.shop.dualBrandedId');
				
		$result = $db
				->table('zs_sd_order as a')
				->fromRaw('zs_sd_order as a WITH(NOLOCK)')
				->join(DB::raw('SHOP00 as s WITH(NOLOCK)'), 's.SHOP_ID', '=', 'a.shopId')
				->select('a.shopId', 'a.productId as erpNo', 'a.price', 'a.qty', 'a.discount')
				#->select('a.shopId', 'a.productId as erpNo')
				#->selectRaw('sum(a.price * a.qty + a.discount) as price_sum')
				#->selectRaw('sum(a.qty) as qty_sum')
				->where('a.saleDate', '>=', $stDate)
				->where('a.saleDate', '<', $endDate)
				->whereIn('s.gid', $authAreaIds)
				->whereIn('a.productId', $erpNos)
				->whereIn('a.shopId', array_keys($dualBrandedShopIds))
				#->groupByRaw('a.shopId, a.productId, s.SHOP_NAME, s.gid')
				->get();
		
		#轉換shop id
		$result = $result->map(function($item, $key) use($dualBrandedShopIds) {
			$item['shopId'] = $dualBrandedShopIds[$item['shopId']];
			return $item;
		});
		
		return $result;
	}
	
	#Backup之後再刪除
	/* Build query string | 八方,御廚
	 * @params: enums
	 * @params: datetime
	 * @params: datetime
	 * @params: array
	 * @params: array
	 * @params: array
	 * @return: array
	 *
	private function _getPrimaryData($brand, $userAreaIds, $stDate, $endDate, $erpNos, $storeIds)
	{
		if ($brand == Brand::BAFANG)
			$db = $this->connectBFPosErp();
		else if ($brand == Brand::BUYGOOD)
			$db = $this->connectBGPosErp();
		else
			return [];
		
		$authAreaIds = AreaLib::toSalesAreaId($brand, $userAreaIds);
		
		#因會無法跑index, sum由PHP計算
		$result = $db
				->table('zs_sd_order as a')
				->fromRaw('zs_sd_order as a WITH(NOLOCK)')
				->join(DB::raw('SHOP00 as s WITH(NOLOCK)'), 's.SHOP_ID', '=', 'a.shopId')
				->select('a.shopId', 'a.productId as erpNo', 'a.price', 'a.qty', 'a.discount')
				->selectRaw('CAST(a.saleDate AS DATE) as saleDate')
				#->select('a.shopId', 'a.productId as erpNo')
				#->selectRaw('sum(a.price * a.qty + a.discount) as price_sum')
				#->selectRaw('sum(a.qty) as qty_sum')
				->selectRaw('CAST(a.saleDate AS DATE) as saleDate')
				->where('a.saleDate', '>=', $stDate)
				->where('a.saleDate', '<', $endDate)
				->whereIn('s.gid', $authAreaIds)
				->whereIn('a.productId', $erpNos)
				->when(! empty($storeIds), function($query) use($storeIds){
					$query->whereIn('a.shopId', $storeIds);
				})
				#->whereNotIn('s.SHOP_ID', $excepts) 由PHP過濾
				#->groupByRaw('a.shopId, a.productId')
				->get(); 
		
		return $result;
	}
	
	/* Build query string | 八方:只有複合店才有的情境
	 * @params: connection
	 * @params: datetime
	 * @params: datetime
	 * @params: array
	 * @params: array
	 * @params: array
	 * @return: array
	 *
	private function _getDualBrandedData($brand, $userAreaIds, $stDate, $endDate, $erpNos, $storeIds)
	{
		if ($brand == Brand::BAFANG)
			return [];
		
		$db = $this->connectBFPosErp();
		$authAreaIds = AreaLib::toSalesAreaId($brand, $userAreaIds);
		$dualBrandedShopIds = config('web.sales.shop.dualBrandedId');
		dd($dualBrandedShopIds);
		$result = $db
				->table('zs_sd_order as a')
				->fromRaw('zs_sd_order as a WITH(NOLOCK)')
				->join(DB::raw('SHOP00 as s WITH(NOLOCK)'), 's.SHOP_ID', '=', 'a.shopId')
				->select('a.shopId', 'a.productId as erpNo', 'a.price', 'a.qty', 'a.discount')
				#->select('a.shopId', 'a.productId as erpNo')
				#->selectRaw('sum(a.price * a.qty + a.discount) as price_sum')
				#->selectRaw('sum(a.qty) as qty_sum')
				->where('a.saleDate', '>=', $stDate)
				->where('a.saleDate', '<', $endDate)
				->whereIn('s.gid', $authAreaIds)
				->whereIn('a.productId', $erpNos)
				->whereIn('a.shopId', array_keys($dualBrandedShopIds))
				#->groupByRaw('a.shopId, a.productId, s.SHOP_NAME, s.gid')
				->get();
		
		#轉換shop id
		$result = $result->map(function($item, $key) use($dualBrandedShopIds) {
			$item['shopId'] = $dualBrandedShopIds[$item['shopId']];
			return $item;
		});
		
		return $result;
	}*/
}
