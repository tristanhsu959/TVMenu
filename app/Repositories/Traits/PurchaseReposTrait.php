<?php

namespace App\Repositories\Traits;

use App\Libraries\Purchase\AreaLib;
use App\Enums\OpCenter;
use App\Enums\Brand;
use App\Enums\Factory;
use Illuminate\Support\Str;

/* nOrder Common Function */
#抽出成trait避免交叉include
trait PurchaseReposTrait
{
	/* 取對應nOrder的設定值
	 * @params: int
	 * @params: array
	 * @return: array
	 */
	/* public function getOpCenterNo($brandId)
	{
		#台北/高雄(取全部)
		if ($brandId == Brand::BAFANG->value OR $brandId == Brand::BUYGOOD->value)
			return OpCenter::toValueArray();
		
		return [];
	} */
	/* 
	public function getBrandNo($brandId)
	{
		$brand = Brand::tryFrom($brandId);
		return $brand->shortCode();
	}
	*/
	public function getFactoryNo($brandId)
	{
		if ($brandId == Brand::BAFANG->value)
			return [Factory::TP->value, Factory::KH->value];
		else
			return [Factory::TS->value, Factory::RL->value];
	} 
	
	/* To Db brandId */
	/* Convert to brandId
	 * @params: 
	 * @return: boolean
	 */
	public function getAllDbBrandId($allowOpCenterIds)
	{
		$brandIds = [];
		$brandIds[] = $brand->value; #主要brand
		
		$dbBrandIds = $this->_convertToDbBrandId($brandIds, $allowOpCenterIds);
		
		return $dbBrandIds;
	}
	
	/* Convert to brandId
	 * @params: 
	 * @return: boolean
	 */
	public function getDbBrandId($brand, $allowOpCenterIds)
	{
		$brandIds = [];
		$brandIds[] = $brand->value; #主要brand
		
		$dbBrandIds = $this->_convertToDbBrandId($brandIds, $allowOpCenterIds);
		
		return $dbBrandIds;
	}
	
	/* Convert to brandId
	 * @params: 
	 * @return: boolean
	 */
	public function getDbBrandIdWithLb($brand, $allowOpCenterIds)
	{
		$brandIds = [];
		$brandIds[] = $brand->value; #主要brand
		$brandIds[] = Brand::LUOBO->value;
		
		#這是要
		$dbBrandIds = $this->_convertToDbBrandId($brandIds, $allowOpCenterIds);
		
		return $dbBrandIds;
	}
	
	private function _convertToDbBrandId($brandIds, $allowOpCenterIds)
	{
		#代入的是enum的定Ids
		$brandMapConfig = config('web.purchase.op_center.brandMap');
		
		#取得DB mapping id
		$dbBrandIds = collect($brandMapConfig)->filter(function($items, $key) use($allowOpCenterIds) {
			return in_array($key, $allowOpCenterIds);
		})->map(function($items, $key) use($brandIds){
			#Tp or Kh array items
			return collect($items)->filter(function($item, $key) use($brandIds){
				return in_array($key, $brandIds);
			})->values()->all();
			
		})->flatten()->unique()->all();
		
		return $dbBrandIds;
	}
	
	/* Get lb brandid
	 * @params: int
	 * @params: array
	 * @return: array
	 */
	public function getAllowLbBrandId($brand)
	{
		#訂貨取資料需,但情境太多,要獨立取
		$brandMap 	= config('web.purchase.op_center.brandMap');
		$lbId 		= Brand::LUOBO->value;
		$allowLbId	= data_get($brandMap, "TP.{$lbId}"); #取TP即可
		
		#八方一律取,不影響
		if ($brand ==  Brand::BAFANG)
			return [$allowLbId];
		
		#御廚及南廠無
		if ($brand == Brand::BUYGOOD OR $brand == Brand::FJVEGGIE)
			return [];
		
		return [];
	}
	
	
}