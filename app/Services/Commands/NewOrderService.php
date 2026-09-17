<?php
#Command Service
namespace App\Services\Commands;

use App\Repositories\Commands\NewOrderRepository;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;
use Exception;


class NewOrderService
{
	private $_data			= [];
    private $_diffDays		= 1;
	
	/* Update locay by lastest days
	 * @params: class
	 * @return: 
	 */
	public function __construct(protected NewOrderRepository $_repository)
	{
	}
	
	/* 取查詢時間區間參數:目前每次只更新1天
	 * @params: int 	讀取資料天數
	 * @params: string	開始日期YYYY-MM-DD
	 * @return: array
	 */
	public function getParams($limitDays, $stDate = NULL)
	{
		$params = [ 
			'stDateTime' 	=> '',
			'endDateTime'	=> '',
		];
		
		try
		{
			$fetchSt = empty($stDate) ? Carbon::now() : Carbon::parse($stDate);
			
			#Order DB是UTC | limit = 1, 表只取當天
			$params['stDateTime']	= $fetchSt->copy()->startOfDay()->setTimezone('UTC')->format('Y-m-d H:i:s'); 
			$params['endDateTime']	= $fetchSt->copy()->addDays($limitDays - 1)->endOfDay()->setTimezone('UTC')->format('Y-m-d H:i:s'); 
			
			#$params['stDateTime']	= $fetchSt->subDay($limitDays - 1)->startOfDay()->setTimezone('UTC')->format('Y-m-d H:i:s'); 
			#$params['endDateTime']	= Carbon::now()->endOfDay()->setTimezone('UTC')->format('Y-m-d H:i:s'); 
			
			return $params;
		}
		catch(Exception $e)
		{
			Log::channel('commandLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception($e->getMessage());
		}
	}
	
	/* Get main data & mapping data from POSDB
	 * @params: array
	 * @params: array
	 * @return: array
	 */
	public function getDataFromNewOrderDB($params)
	{
		try
		{
			#Get data : op center = [1, 2]
			$result = $this->_repository->getOrderData($params['stDateTime'], $params['endDateTime']);
			
			return $result;
		}
		catch(Exception $e)
		{
			Log::channel('commandLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('讀取New Order DB資料失敗');
		}
	}
	
	/* Get main data & mapping data from POSDB
	 * @params: array
	 * @params: array
	 * @return: array
	 */
	public function saveToLocalDB($data, $params)
	{
		$stDateTime		= $params['stDateTime'];
		$endDateTime	= $params['endDateTime'];
			
		$this->_repository->updateOrderToLocal($data, $stDateTime, $endDateTime);
	}
}
