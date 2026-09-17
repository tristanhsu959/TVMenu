<?php

namespace App\Repositories\Commands;

use App\Repositories\Repository;
use App\Libraries\ShopLib;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Exception;

class PosBgRepository extends Repository
{
	#MSSQL
	public function __construct()
	{
		
	}
	
	/* 取主資料-BuyGood
	 * @params: datetime
	 * @params: datetime
	 * @params: array
	 * @return: array
	 */
	public function getBgSaleData($startDateTime, $endDateTime, $productIds)
	{
		$db = $this->connectBGPosErp('SALE01 as a');
		$result = $this->_getSaleResult($db, $startDateTime, $endDateTime, $productIds);
		
		return $result;
	}
	
	/* 取複合店資料
	 * @params: datetime
	 * @params: datetime
	 * @params: array
	 * @params: array
	 * @return: array
	 */
	public function getBfSaleData($startDateTime, $endDateTime, $productIds, $shopIds)
	{
		$db = $this->connectBFPosErp('SALE01 as a');
		$result = $this->_getSaleResult($db, $startDateTime, $endDateTime, $productIds, $shopIds);
		
		return $result;
	}
	
	/* Build query string | 新品:八方/梁社漢共用
	 * @params: query builder
	 * @params: datetime
	 * @params: datetime
	 * @params: array
	 * @params: string
	 * @params: array
	 * @return: array
	 */
	private function _getSaleResult($db, $startDateTime, $endDateTime, $productIds, $shopIds = NULL)
	{
		###不是sale_price, 要取總價的欄位
		$query = $db
				->select('a.SHOP_ID', 'b.SHOP_NAME', 'a.PROD_ID', 'a.QTY', 'a.SALE_PRICE', 'a.order_time', 'a.TASTE_MEMO')
				->join('SHOP00 as b', 'a.SHOP_ID', '=', 'b.SHOP_ID')
				->whereBetween('a.order_time', [$startDateTime, $endDateTime])
				->whereIn('a.PROD_ID', $productIds)
				->when($shopIds, function ($query, $shopIds) {
					return $query->whereIn('a.SHOP_ID', $shopIds);
				});
				
		Log::channel('commandLog')->info($query->toRawSql(), [ __class__, __function__, __line__]);
		
		return $query->get();
	}
	
	/* Insert pos data to mariadb by initialize
	 * @params: string
	 * @params: array
	 * @return: boolean
	 */
	public function insertPosToLocal($posData)
	{
		/* 每筆訂單的資料格式
		[
		  "SHOP_ID" => "235001"
		  "SHOP_NAME" => "御廚中和直營店"
		  "PROD_ID" => "235001"
		  "QTY" => "1.0000"
		  "SALE_PRICE" => "1.0000"
		  "order_time" => "2025-12-19 17:13:11.000"
		  "TASTE_MEMO" => "XXX"
		]
		*/
		
		$db = $this->connectSalesDashboard();
		
		$result = [];
		foreach($posData as $row) 
		{
			$data['shopId']		= $row['SHOP_ID'];
			$data['shopName']	= $row['SHOP_NAME'];
			$data['areaId']		= ShopLib::getAreaIdByShopId($row['SHOP_ID']);
			$data['qty']		= floatval($row['QTY']);
			$data['price']		= intval($row['SALE_PRICE']);
			$data['saleDate']	= (new Carbon($row['order_time']))->format('Y-m-d');
			$data['taste']		= Str::of($row['TASTE_MEMO'])->explode(';')->toJson();
			$data['updateAt'] 	= now()->format('Y-m-d H:i:s');
				
			$result[] = $data;
		}
		
		collect($result)->chunk(1000)->each(function ($chunk) {
			$db->table('new_release')->insert($chunk->toArray());
		});
		
		return TRUE;
	}
}
