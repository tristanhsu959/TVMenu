<?php

namespace App\Repositories;

#use App\Repositories\Traits\PurchaseReposTrait;
use App\Facades\PurchaseManager;
use App\Facades\PosManager;
use App\Libraries\Purchase\AreaLib;
use App\Enums\OpCenter;
use App\Enums\Brand;
use App\Enums\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Exception;


class PurchaseSalesRepository extends Repository
{
	#use PurchaseReposTrait;
	
	public function __construct()
	{
		
	}
	
	/* 取有效門店清單(取法不同於其它功能)
	 * @params: enum
	 * @params: array
	 * @return: array
	 */
	public function getActiveStoreListFromPurchase($brand, $areaIds, $storeName)
	{
		$brandId = $brand->value;
		$authAreaIds = AreaLib::toPurchaseAreaId($brand, $areaIds);
		
		$db = $this->connectNewOrder();
		$result = $db
			->table(DB::raw('Store as s WITH(NOLOCK)'))
			->join('Area as ar', 'ar.Id', '=', 's.AreaId')
			->join('StoreCar as sc', 'sc.StoreId', '=', 's.Id')
			->select('ar.Id as areaId', 's.Id as storeId', 's.No as storeNo', 's.Name as storeName')
			->addSelect('s.PosId as posId', 's.VATNumber as vatNumber', 's.ErpNo as erpNo')
			->addSelect('s.BossName as bossName', 's.Address as address', 's.OpenDate as openDate')
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
			->when(!empty($storeName), function($query) use($storeName){
				$query->whereAny(['s.Name'], 'like', DB::raw("N'%$storeName%'"));
			})
			->whereNull('s.CloseDate')
			->whereIn('s.AreaId', $authAreaIds)
			->whereNotIn('s.No', config("web.purchase.store.except.{$brandId}"))#->ddRawSql();
			->get()
			->toArray(); 
		
		return $result;
	}
	
	/* 取有效門店清單By id
	 * @params: enum
	 * @params: int
	 * @return: array
	 */
	public function getPurchaseStoreInfoById($storeId)
	{
		#已過濾area權限, 不用再過濾
		$db = $this->connectNewOrder();
		$result = $db
			->table(DB::raw('Store as s WITH(NOLOCK)'))
			->select('s.Id as storeId', 's.No as storeNo', 's.Name as storeName')
			->addSelect('s.PosId as posId', 's.VATNumber as vatNumber', 's.ErpNo as erpNo')
			->where('s.Id', '=', $storeId)
			->get()
			->first(); 
		
		return $result;
	}
	
	/*************** New Purchase Order ***************/
	/* 取訂貨訂單資料 By records 
	 * @params: enums
	 * @params: datetime
	 * @params: datetime
	 * @params: array
	 * @return: array
	 */
	public function getPurchaseOrderByStore($brand, $stDate, $endDate, $storeId)
	{
		#to UTC Time
		$stDate		= (new Carbon($stDate))->utc()->format('Y-m-d H:i:s');
		$endDate	= (new Carbon($endDate))->utc()->format('Y-m-d H:i:s');
		$brandId 	= $brand->value;
		#$orderStatus = config('web.purchase.order.status.active');
		
		$db = $this->connectNewOrder();
		$result = $db
			->table(DB::raw('[Order] as a WITH(NOLOCK)'))
			->join(DB::raw('OrderSub as b WITH(NOLOCK)'), 'b.OrderId', '=', 'a.Id')
			->join(DB::raw('Product as p WITH(NOLOCK)'), 'p.Id', '=', 'b.ProductId')
			->join(DB::raw('StoreCar as sc WITH(NOLOCK)'), 'sc.StoreId', '=', 'a.StoreId')
			->join(DB::raw('Factory as f WITH(NOLOCK)'), 'f.Id', '=', 'sc.FactoryId')
			->selectRaw('CAST(DATEADD(HOUR, 8, a.ExpectedDate) AS DATE) as expectedDate')
			->addSelect('b.Quantity as qty', 'b.Money as amount')
			->addSelect('p.Name as productName', 'p.OldNo as shortCode', 'p.Memo as memo')
			->whereExists(function ($query) use($brandId) {
				$query->select(DB::raw(1))
					->from('OperationCenter as oc')
					->whereColumn('oc.Id', 'a.OperationCenterId')
					->whereIn('oc.No', $this->getOpCenterNo($brandId));
			})
			->whereExists(function ($query) use($brandId) {
				$query->select(DB::raw(1))
					->from('Factory as ft')
					->whereColumn('ft.Id', 'sc.FactoryId')
					->whereIn('ft.No',  $this->getFactoryNo($brandId));
			})
			->where('a.ExpectedDate', '>=', $stDate)
			->where('a.ExpectedDate', '<', $endDate)
			->where('a.StoreId', '=', $storeId)
			#->whereIn('a.State', $orderStatus)
			->where('b.Money', '>', 0)
			->where('p.ErpNo', '!=', '')
			->get()
			->toArray();
		
		return $result;
	}
	
	/*************** Pos Order ***************/
	/* Sale data | 新品:八方/梁社漢共用
	 * @params: query builder
	 * @params: datetime
	 * @params: datetime
	 * @params: array
	 * @params: string
	 * @params: array
	 * @return: array
	 */
	public function getPosOrderByPosId($brand, $stDate, $endDate, $posId)
	{
		$primaryData 		= $this->_getPrimaryData($brand, $stDate, $endDate, $posId);
		$dualBrandedData	= $this->_getDualBrandedData($brand, $stDate, $endDate, $posId);
		
		$dualBrandedData = collect($dualBrandedData)->filter(function($item, $key){
			#排除非UC的item
			return Str::startsWith($item['erpNo'], 'UC');
		})->toArray();
		
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
	private function _getPrimaryData($brand, $stDate, $endDate, $posId)
	{
		if ($brand == Brand::BAFANG)
			$db = $this->connectBFPosErp();
		else if ($brand == Brand::BUYGOOD)
			$db = $this->connectBGPosErp();
		else
			return [];
		
		#因會無法跑index, sum由PHP計算
		$result = $db
				->table(DB::raw('zs_sd_order as a WITH(NOLOCK)'))
				->join(DB::raw('PRODUCT00 as p WITH(NOLOCK)'), 'p.PROD_ID', '=', 'a.productId')
				->select('a.productId as erpNo', 'p.PROD_NAME1 as productName', 'a.price', 'a.qty', 'a.discount')
				->where('a.saleDate', '>=', $stDate)
				->where('a.saleDate', '<', $endDate)
				->where('a.shopId', '=', $posId)
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
	private function _getDualBrandedData($brand, $stDate, $endDate, $posId)
	{
		#複合店御廚Order會存在八方，故只有御廚時需要到poserp取order
		if ($brand == Brand::BAFANG)
			return [];
		
		$mappingPosId = PosManager::getDualBrandedMappingId($posId);
		
		if (! PosManager::isDualBranded($posId) OR empty($mappingPosId))
			return [];
		
		$db = $this->connectBFPosErp();
		
		$result = $db
				->table(DB::raw('zs_sd_order as a WITH(NOLOCK)'))
				->join(DB::raw('PRODUCT00 as p WITH(NOLOCK)'), 'p.PROD_ID', '=', 'a.productId')
				->select('a.productId as erpNo', 'p.PROD_NAME1 as productName', 'a.price', 'a.qty', 'a.discount')
				->where('a.saleDate', '>=', $stDate)
				->where('a.saleDate', '<', $endDate)
				->where('a.shopId', '=', $mappingPosId)
				#->where('a.productId', 'like', 'UC%') 很慢
				->get(); 
			
		return $result;
	}
}
