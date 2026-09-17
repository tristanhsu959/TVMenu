<?php

namespace App\Repositories\Commands;

use App\Repositories\Repository;
use App\Libraries\ShopLib;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;
use Exception;

#新訂貨系統DB
class NewOrderRepository extends Repository
{
	#MSSQL
	public function __construct()
	{
		
	}
	
	/* 取訂貨資料-不分Brand
	 * @params: query builder
	 * @params: datetime
	 * @params: datetime
	 * @params: array
	 * @params: string
	 * @params: array
	 * @return: array
	 */
	public function getOrderData($startDateTime, $endDateTime)
	{
		$db = $this->connectNewOrder('Order as a');
		$query = $db
				->select('a.No as orderNo', 'a.ExpectedDate', 'a.Money as orderAmount')
				->addSelect('g.No as productNo', 'g.Name as productName', 'h.Name as productType')
				->addSelect('b.UnitName as productUnit', 'b.Price as productPrice', 'b.Quantity as productQuantity', 'b.Money as productAmount')
				->addSelect('c.Name as storeName', 'c.No as storeNo', 'f.Name as storeType')
				->addSelect('d.name as area', 'e.Name as brand', 'e.No as brandNo')
				->join('OrderSub as b', 'b.OrderId', '=', 'a.Id')
				->join('Store as c', 'c.Id', '=', 'a.StoreId')
				->join('Area as d', 'd.Id', '=', 'c.AreaId')
				->join('Brand as e', 'e.Id', '=', 'c.BrandId')
				->join('BusinessType as f', 'f.Id', '=', 'c.BusinessTypeId')
				->join('Product as g', 'g.Id', '=', 'b.ProductId')
				->join('ProductType as h', 'h.Id', '=', 'g.ProductTypeId')
				->where('a.ExpectedDate', '>=', $startDateTime)
				->where('a.ExpectedDate', '<=', $endDateTime)
				->where(function ($db) {
					$db->where('a.OperationCenterId', '=', 1)
						->orWhere('a.OperationCenterId', '=', 2);
				})
				->where('a.Money', '>', 0)
				->where('b.Quantity', '>', 0);
				
		Log::channel('commandLog')->info($query->toRawSql(), [ __class__, __function__, __line__]);
		
		return $query->get();
	}
	
	/* Insert data to mariadb
	 * @params: string
	 * @params: array
	 * @params: date
	 * @params: date
	 * @return: boolean
	 */
	public function updateOrderToLocal($srcData, $stDate, $endDate)
	{
		#Initialize前要先清空
		$db = $this->connectSalesDashboard();
		$db->table('new_order')
			->where('ExpectedDate', '>=', $stDate)
			->where('ExpectedDate', '<=', $endDate)
			->delete();
		
		foreach($srcData as $row)
		{
			$data['orderNo']		= $row['orderNo'];
			$data['expectedDate']	= $row['ExpectedDate'];
			$data['orderAmount']	= $row['orderAmount'];
			$data['productNo']		= $row['productNo'];
			$data['productName']	= $row['productName'];
			$data['productType']	= $row['productType'];
			$data['productUnit']	= $row['productUnit'];
			$data['productPrice']	= $row['productPrice'];
			$data['productQuantity']= $row['productQuantity'];
			$data['productAmount']	= $row['productAmount'];
			
			$data['storeName']		= $row['storeName'];
			$data['storeNo']		= $row['storeNo'];
			$data['storeType']		= $row['storeType'];
			$data['area']			= $row['area'];
			$data['areaId']			= ShopLib::getAreaIdByShopId($row['storeNo']);
			$data['brand']			= $row['brand'];
			$data['brandNo']		= $row['brandNo'];
			
			$data['updateAt'] 		= now()->format('Y-m-d H:i:s');
				
			$db->table('new_order')->insert($data);
		}
		
		return TRUE;
	}
}
