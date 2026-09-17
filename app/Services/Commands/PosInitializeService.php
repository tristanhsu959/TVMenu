<?php
#Command Service
namespace App\Services\Commands;

use App\Repositories\Commands\PosRepository;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;
use Exception;


class PosInitializeService
{
	private $_configKey 	= '';
	private $_data			= [];
    
	/* Initialize once
	 * @params: class
	 * @return: 
	 */
	public function __construct(protected PosRepository $_repository)
	{
	}
	
	/* Set Config
	 * @params: string
	 * @return: 
	 */
	public function setConfig($configKey)
	{
		$this->_configKey = $configKey;
	}
	
	/* 取Config設定及查詢時間區間參數
	 * @params: string
	 * @params: string
	 * @return: array
	 */
	public function getParams()
	{
		$params = [ 
			'stDate' 	=> '',
			'endDate'	=> '',
			'bgIds'		=> [],
			'bfIds'		=> [],
			'valueAdded'=> []
		];
		
		try
		{
			$config = config("buygood.new_release.products.{$this->_configKey}");
			
			$brand = data_get($config, 'brand');
						
			#計算initialize要取的時間, 以開賣日起算
			list($stDate, $endDate) = $this->_calcFetchTime($config['saleDate']);
			
			data_set($params, 'stDate', $stDate);
			data_set($params, 'endDate', $endDate);
			data_set($params, 'bgIds', data_get($config, 'ids.main'));
			data_set($params, 'bfIds', data_get($config, 'ids.mapping'));
			data_set($params, 'valueAdded', data_get($config, 'valueAdded', []));
				
			return $params;
		}
		catch(Exception $e)
		{
			Log::channel('commandLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception($e->getMessage());
		}
	}
	
	/* 取查詢時間區間參數
	 * @params: string
	 * @return: array
	 */
	private function _calcFetchTime($saleDate)
	{
		try
		{
			if (empty($saleDate))
				throw new Exception('開賣日未設定');
			
			$stDate		= (new Carbon($saleDate))->format('Y-m-d'); #開賣日
			$endDate 	= Carbon::now()->subDay()->format('Y-m-d'); #取到前一天即可
			
			return [$stDate, $endDate];
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
	public function getDataFromPosDB($params)
	{
		try
		{
			#Pos有存到time, 以防萬一
			$stDateTime		= (new Carbon($params['stDate']))->format('Y-m-d 00:00:00');
			$endDateTime	= (new Carbon($params['endDate']))->format('Y-m-d 23:59:59');
			
			#Get main data first
			$mainData = $this->_repository->getBgSaleData($stDateTime, $endDateTime, $params['bgIds'], $params['valueAdded']);
			
			if (! empty($params['bfIds'])) #梁社漢新品時會有值
			{
				#取複合店Shop id
				$shopIdMapping 	= config('buygood.new_release.multiBrandShopidMapping');
				$shopIds 		= array_keys($shopIdMapping);
			
				$mappingData = $this->_repository->getBfSaleData($stDateTime, $endDateTime, $params['bfIds'], $params['valueAdded'], $shopIds);
				
				#避免未抓到資料的狀況
				if (! empty($mappingData))
				{
					#轉換對應的BG shop id
					$mappingData = $mappingData->map(function($item, $key) use ($shopIdMapping) {
						$item['SHOP_ID'] = $shopIdMapping[$item['SHOP_ID']];
						return $item;
					});
					
					$mainData = $mainData->merge($mappingData);
				}
			}
			
			/* 每筆訂單的資料格式
			["SHOP_ID" => "235001"
			  "QTY" => "1.0000"
			  "SALE_DATE" => "2025-12-19 17:13:11.000"
			  "SHOP_NAME" => "御廚中和直營店"
			]
			*/
			return $mainData;
		}
		catch(Exception $e)
		{
			Log::channel('commandLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('讀取POS DB資料失敗');
		}
	}
	
	/* Get main data & mapping data from POSDB
	 * @params: array
	 * @params: array
	 * @return: array
	 */
	public function saveToLocalDB($posData)
	{
		$this->_repository->insertPosToLocal($this->_configKey, $posData);
	}
}
