<?php

namespace App\Manager\Repositories;

use App\Repositories\Repository;
use App\Enums\OpCenter;
use App\Enums\Brand;
use App\Enums\Factory;
use Illuminate\Support\Facades\DB;

#舊訂貨系統
class LegacyRepository  extends Repository
{
	
	
	/* 取TP資料-僅追加(以防有獨立取資料的狀況, 故獨立出來)
	 * @params: datetime
	 * @params: datetime
	 * @params: array
	 * @return: array
	 */
	public function getTpExtraData($stDate, $endDate, $productCodes)
	{
		$db = $this->connectOrderTP();
		$result = $this->_getExtraOrder($db, 'OrderItem', $stDate, $endDate, $productCodes);
		$resultOld = $this->_getExtraOrder($db, 'OrderItem_old', $stDate, $endDate, $productCodes);
		
		return $result->merge($resultOld);
	}
	
	/* 取KH資料-僅追加(以防有獨立取資料的狀況, 故獨立出來)
	 * @params: datetime
	 * @params: datetime
	 * @params: array
	 * @return: array
	 */
	public function getKhExtraData($stDate, $endDate, $productCodes)
	{
		$db = $this->connectOrderKH();
		$result = $this->_getExtraOrder($db, 'OrderItem', $stDate, $endDate, $productCodes);
		$resultOld = $this->_getExtraOrder($db, 'OrderItem_old', $stDate, $endDate, $productCodes);
		
		return $result->merge($resultOld);
	}
	
	/* 取TP資料-僅追加(以防有獨立取資料的狀況, 故獨立出來)
	 * @params: datetime
	 * @params: datetime
	 * @params: array
	 * @return: array
	 */
	public function getTsExtraData($stDate, $endDate, $productCodes)
	{
		$db = $this->connectOrderTS();
		$result = $this->_getExtraOrder($db, 'OrderItem', $stDate, $endDate, $productCodes);
		$resultOld = $this->_getExtraOrder($db, 'OrderItem_old', $stDate, $endDate, $productCodes);
		
		return $result->merge($resultOld);
	}
	
	/* 取KH資料-僅追加(以防有獨立取資料的狀況, 故獨立出來)
	 * @params: datetime
	 * @params: datetime
	 * @params: array
	 * @return: array
	 */
	public function getRlExtraData($stDate, $endDate, $productCodes)
	{
		$db = $this->connectOrderRL();
		$result = $this->_getExtraOrder($db, 'OrderItem', $stDate, $endDate, $productCodes);
		$resultOld = $this->_getExtraOrder($db, 'OrderItem_old', $stDate, $endDate, $productCodes);
		
		return $result->merge($resultOld);
	}
	
	/* Build query string | 追加
	 * @params: query builder
	 * @params: datetime
	 * @params: datetime
	 * @return: array
	 */
	private function _getExtraOrder($db, $table, $stDate, $endDate, $productNos)
	{
		$exceptShopIds = ['10006000', '999111', '999999', '10007000', '1000', '1100000'];
		
		#union all有點慢
		$result = $db->table("{$table} as a")
				->fromRaw("{$table} as a WITH(NOLOCK)")
				->select('a.Idx as legacyId', 'a.ProductNo as shortCode', 'a.ProductName as productName')
				->addSelect('a.AccNo as storeNo', 'a.OrderDate as expectedDate')
				->addSelect('a.Amount as qty', 'a.Money as amount')
				#->selectRaw('sum(a.Amount) as qty, sum(a.Money) as amount')
				#->selectRaw('LEFT(CAST(a.OrderDate AS DATE), 7) as expectedDate')
				->where('a.OrderDate', '>=', $stDate)
				->where('a.OrderDate', '<', $endDate)
				->where('a.Ps', '!=', 'OMS') #追加會是空白
				->when(($productNos !== FALSE), function($query) use($productNos){
					$query->whereIn('a.ProductNo', $productNos);
				})
				->where('a.Money', '>', 0)
				->whereNotIn('a.AccNo', $exceptShopIds)
				#->groupBy('a.ProductNo', 'a.ProductName', 'a.AccNo', 'a.ProductName')
				#->groupByRaw('LEFT(CAST(a.OrderDate AS DATE), 7)') 
				->get();
				
		return $result;
	}
	
	
	#======================= 員購/公關 =======================
	/* 
	 * @params: datetime
	 * @params: datetime
	 * @params: array
	 * @return: array
	 */
	public function getTpDataByAccNo($stDate, $endDate, $accNo)
	{
		$db = $this->connectOrderTP();
		$result = $this->_getEmployeePrOrder($db, '', $stDate, $endDate, $accNo);
		$resultOld = $this->_getEmployeePrOrder($db, '_old', $stDate, $endDate, $accNo);
		
		return $result->merge($resultOld);
	}
	
	/* 取KH資料-僅追加(以防有獨立取資料的狀況, 故獨立出來)
	 * @params: datetime
	 * @params: datetime
	 * @params: array
	 * @return: array
	 */
	public function getKhDataByAccNo($stDate, $endDate, $accNo)
	{
		$db = $this->connectOrderKH();
		$result = $this->_getEmployeePrOrder($db, '', $stDate, $endDate, $accNo);
		$resultOld = $this->_getEmployeePrOrder($db, '_old', $stDate, $endDate, $accNo);
		
		return $result->merge($resultOld);
	}
	
	/* 取TP資料-僅追加(以防有獨立取資料的狀況, 故獨立出來)
	 * @params: datetime
	 * @params: datetime
	 * @params: array
	 * @return: array
	 */
	public function getTsDataByAccNo($stDate, $endDate, $accNo)
	{
		$db = $this->connectOrderTS();
		$result = $this->_getEmployeePrOrder($db, '', $stDate, $endDate, $accNo);
		$resultOld = $this->_getEmployeePrOrder($db, '_old', $stDate, $endDate, $accNo);
		
		return $result->merge($resultOld);
	}
	
	/* 取KH資料-僅追加(以防有獨立取資料的狀況, 故獨立出來)
	 * @params: datetime
	 * @params: datetime
	 * @params: array
	 * @return: array
	 */
	public function getRlDataByAccNo($stDate, $endDate, $accNo)
	{
		$db = $this->connectOrderRL();
		$result = $this->_getEmployeePrOrder($db, '', $stDate, $endDate, $accNo);
		$resultOld = $this->_getEmployeePrOrder($db, '_old', $stDate, $endDate, $accNo);
		
		return $result->merge($resultOld);
	}
	
	/* Build query string | 追加
	 * @params: query builder
	 * @params: datetime
	 * @params: datetime
	 * @return: array
	 */
	private function _getEmployeePrOrder($db, $postfix, $stDate, $endDate, $accNo)
	{
		$table 		= "OrderList{$postfix}";
		$joinTable 	= "OrderItem{$postfix}";
		
		$result = $db->table("{$table} as a")
				->fromRaw("{$table} as a WITH(NOLOCK)")
				->join(DB::raw("{$joinTable} as b WITH(NOLOCK)"), 'b.OrderNo', '=', 'a.OrderNo')
				->select('a.OrderNo as orderNo', 'a.OrderDate as orderDate', 'a.Money as orderAmount', 'a.Ps as memo')
				->addSelect('b.ProductNo as shortCode', 'b.ProductName as productName', 'Unit as unit')
				->addSelect('b.Price as price', 'b.Amount as qty', 'b.Money as totalAmount')
				->where('a.AccNo', '=', $accNo)
				->where('a.OrderDate', '>=', $stDate)
				->where('a.OrderDate', '<', $endDate)
				->where('a.Money', '>', 0)
				->get();
				
		return $result;
	}
	
	
	#======================= 追加(含單頭單身) =======================
	/* 取TP資料-僅追加(以防有獨立取資料的狀況, 故獨立出來)
	 * @params: datetime
	 * @params: datetime
	 * @params: array
	 * @return: array
	 */
	public function getTpFullExtraData($stDate, $endDate)
	{
		$db = $this->connectOrderTP();
		$result = $this->_getFullExtraOrder($db, '', $stDate, $endDate);
		$resultOld = $this->_getFullExtraOrder($db, '_old', $stDate, $endDate);
		
		return $result->merge($resultOld);
	}
	
	/* 取KH資料-僅追加(以防有獨立取資料的狀況, 故獨立出來)
	 * @params: datetime
	 * @params: datetime
	 * @params: array
	 * @return: array
	 */
	public function getKhFullExtraData($stDate, $endDate)
	{
		$db = $this->connectOrderKH();
		$result = $this->_getFullExtraOrder($db, '', $stDate, $endDate);
		$resultOld = $this->_getFullExtraOrder($db, '_old', $stDate, $endDate);
		
		return $result->merge($resultOld);
	}
	
	/* 取TP資料-僅追加(以防有獨立取資料的狀況, 故獨立出來)
	 * @params: datetime
	 * @params: datetime
	 * @params: array
	 * @return: array
	 */
	public function getTsFullExtraData($stDate, $endDate)
	{
		$db = $this->connectOrderTS();
		$result = $this->_getFullExtraOrder($db, '', $stDate, $endDate);
		$resultOld = $this->_getFullExtraOrder($db, '_old', $stDate, $endDate);
		
		return $result->merge($resultOld);
	}
	
	/* 取KH資料-僅追加(以防有獨立取資料的狀況, 故獨立出來)
	 * @params: datetime
	 * @params: datetime
	 * @params: array
	 * @return: array
	 */
	public function getRlFullExtraData($stDate, $endDate)
	{
		$db = $this->connectOrderRL();
		$result = $this->_getFullExtraOrder($db, '', $stDate, $endDate);
		$resultOld = $this->_getFullExtraOrder($db, '_old', $stDate, $endDate);
		
		return $result->merge($resultOld);
	}
	
	/* Build query string | 追加
	 * @params: query builder
	 * @params: datetime
	 * @params: datetime
	 * @return: array
	 */
	private function _getFullExtraOrder($db, $postfix, $stDate, $endDate)
	{
		$table 		= "OrderList{$postfix}";
		$joinTable 	= "OrderItem{$postfix}";
		
		#另加上排除員購及公關
		$exceptShopIds = ['10006000', '999111', '999999', '10007000', 
							'1000', '1001', '1002', '1003', '1100000', '1100100', 
							'1100', '1102', '1033']; #1033丹堤
		
		$result = $db->table("{$table} as a")
				->fromRaw("{$table} as a WITH(NOLOCK)")
				->join(DB::raw("{$joinTable} as b WITH(NOLOCK)"), 'b.OrderNo', '=', 'a.OrderNo')
				->select('a.AccNo as storeNo', 'a.OrderNo as orderNo', 'a.OrderDate as orderDate', 'a.Money as orderAmount', 'a.Ps as memo')
				->addSelect('b.ProductNo as shortCode', 'b.ProductName as productName', 'Unit as unit')
				->addSelect('b.Price as price', 'b.Amount as qty', 'b.Money as totalAmount')
				->where('a.OrderDate', '>=', $stDate)
				->where('a.OrderDate', '<', $endDate)
				->where('b.Ps', '!=', 'OMS') #追加單身會是空白, 單頭要與except一起判別才行
				->where('a.Money', '>', 0)
				->whereNotIn('a.AccNo', $exceptShopIds)
				->get();
				
		return $result;
	}
}
