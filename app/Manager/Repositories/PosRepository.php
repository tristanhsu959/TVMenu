<?php

namespace App\Manager\Repositories;

use App\Repositories\Repository;
use App\Enums\Brand;
use App\Libraries\Sales\AreaLib;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/* POS DB Common 
 * 不再分purchase, sales目錄
*/
class PosRepository extends Repository
{
	/* 取所有門店資料(有些門店目前可能已Close,故統計資料須抓全部的shop)
	 * @params: enums
	 * @params: array
	 * @params: array
	 * @params: string
	 * @return: array
	 */
	public function getStoreList($brand, $userAreaIds, $type = FALSE, $name = FALSE)
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
		
		#false轉換後會是0
		$authAreaIds = AreaLib::toSalesAreaId($brand, $userAreaIds);
		
		$result = $db->table('SHOP00 as a')
			->join('shop_kind as b', 'b.sk_id', '=', 'a.shop_kind')
			->select('a.SHOP_ID as shopId', 'a.SHOP_NAME as storeName', 'a.gid as areaId', 'a.closedown', 'a.erp_shopid as storeKey')
			->addSelect('b.sk_id as typeId', 'b.Sk_name as typeName')
			#->where('a.closedown', '=', 0)
			->whereIn('a.gid', $authAreaIds)
			->when(! empty($type), function ($query) use ($type) {
				$query->whereIn('a.shop_kind', $type);
			})
			->when(! empty($name), function ($query) use ($name) {
				$query->WhereAny(['a.SHOP_NAME'], 'like', "%{$name}%");
			})
			->whereNotIn('a.SHOP_ID', $excepts)
			->orderBy('a.SHOP_ID')
			->get();
		
		return $result;
	}
	
	/* 取Active門店資料(POS有在運作的才會在此Table)
	 * @params: enums
	 * @params: array
	 * @return: array
	 */
	public function getHptransStoreList($brand, $userAreaIds, $type = FALSE, $name = FALSE)
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
		
		$authAreaIds = AreaLib::toSalesAreaId($brand, $userAreaIds);
			
		$result = $db->table('hptrans_shop as h')
			->join('SHOP00 as a', 'a.SHOP_ID', '=', 'h.hptrs_shop')
			->join('shop_kind as b', 'b.sk_id', '=', 'a.shop_kind')
			->select('a.SHOP_ID as shopId', 'a.SHOP_NAME as storeName', 'a.gid as areaId')
			->addSelect('b.sk_id as typeId', 'b.Sk_name as typeName')
			->where('a.closedown', '=', 0)
			->whereIn('a.gid', $authAreaIds)
			->when(! empty($type), function ($query) use ($type) {
				$query->whereIn('a.shop_kind', $type);
			})
			->when(! empty($name), function ($query) use ($name) {
				$query->WhereAny(['a.SHOP_NAME'], 'like', "%{$name}%");
			})
			->whereNotIn('a.SHOP_ID', $excepts)
			->orderBy('a.SHOP_ID')
			->get();
		
		return $result;
	}
}