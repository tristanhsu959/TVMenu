<?php

namespace App\Manager\Repositories;

use App\Repositories\Repository;
#use App\Repositories\Traits\PurchaseReposTrait;
use App\Enums\OpCenter;
use App\Enums\Brand;
use App\Enums\Factory;

#舊訂貨系統in Local
class LocalLegacyRepository  extends Repository
{
	#use PurchaseReposTrait;
	
	public function __construct()
	{
	}
	
	/* Build query string | 追加
	 * @params: query builder
	 * @params: datetime
	 * @params: datetime
	 * @params: array => productCodes = FALSE => 全取
	 * @return: array
	 */
	public function getExtraData($brand, $factoryNos, $stDate, $endDate, $productCodes)
	{
		#只能用factory no來分brand
		$brandId = $brand->value;
		
		$db = $this->connectSalesDashboard();
		#union all有點慢
		$result = $db->table('legacy_extra_order as o')
				->select('o.expectedDate', 'o.storeNo', 'o.shortCode', 'o.productName')
				->addSelect('o.factoryNo', 'o.factoryName', 'o.qty', 'o.amount')
				->where('o.expectedDate', '>=', $stDate)
				->where('o.expectedDate', '<', $endDate)
				->when(($productCodes !== FALSE), function($query) use($productCodes){
					$query->whereIn('o.shortCode', $productCodes);
				})
				->where('o.amount', '>', 0)
				->whereIn('o.factoryNo', $factoryNos)
				->get()
				->toArray();
				
		return $result;
	}
}
