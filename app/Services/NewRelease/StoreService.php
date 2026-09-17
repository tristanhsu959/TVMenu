<?php

namespace App\Services\NewRelease;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Carbon\CarbonPeriod;
use Exception;
use OpenSpout\Writer\Common\Creator\WriterEntityFactory;
use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;

#partial Service
class StoreService
{
	public function __construct()
	{
	}
	
	/* ====================== 主流程 By Name ====================== */
	/* 店別每日銷售
	 * @params: array
	 * @params: int
	 * @return: array
	 */
	public function parsing($params)
	{
		/* Output
		[
		330002 => [
			"shopId" => "420001"
			"storeName" => "御廚豐原向陽店"
			"areaId" => 4
			"areaName" => "中彰投區"
			"dayQty" =>  [
				"2025-09-15" => 6.0
				"2025-09-14" => 7.0
			]
			"totalQty" => 13.0
			"totalAvg" => 6.5
		]
		*/
		
		$params->set('shop.header', []);
		$params->set('shop.data', []);
		
		$baseData	= $params->baseData;
		$totalDays 	= $params->totalDays;
		
		#會有無設定區域權限的狀況, 須判別
		if (empty($baseData))
			return FALSE;
		
		$header = ['areaName' => '區域', 'shopId' => 'POS店號', 'storeKey' => '門店代號', 'storeName' => '門店名稱', 
					'dayQty' => $params->dayRange, 
					'totalQty' => '銷售總量', 'totalAvg' => '平均銷售數量'
				];
		
		$params->set('shop.header', $header);
		
		#已是不重複門店及資料
		$result = collect($baseData)->sortBy('areaId')->map(function($item, $key) use($totalDays) {
			
			$temp['storeKey']	= $item['storeKey'];
			$temp['shopId']		= $item['posId'];
			$temp['storeName'] 	= $item['storeName'];
			$temp['areaId'] 	= $item['areaId'];
			$temp['areaName'] 	= $item['areaName'];
			$temp['dayQty'] 	= $item['data'];
			
			#計算=>銷售總量|平均銷售數量
			$temp['totalQty'] = array_sum($temp['dayQty']); #銷售量總和
			$temp['totalAvg'] = empty($temp['totalQty']) ? 0 : round($temp['totalQty'] / $totalDays, 1); #平均銷售數量:銷售量總和/天數
			
			return $temp; 
		})->values()->all();
		
		$params->set('shop.data', $result);
	}
	
	/* Build data for export
	 * @params: array
	 * @return: array
	 */
	public function buildExport($shopData)
	{
		$header = Arr::flatten($shopData['header']);
		$export[] = $header;
		
		foreach($shopData['data'] as $shopId => $data)
		{
			$row = [];
			$row[] = data_get($data, 'areaName');
			$row[] = data_get($data, 'shopId');
			$row[] = data_get($data, 'storeKey');
			$row[] = data_get($data, 'storeName');
			
			foreach($shopData['header']['dayQty'] as $date)
			{
				$row[] = data_get($data, "dayQty.{$date}", 0);
			}
			
			$row[] = data_get($data, 'totalQty');
			$row[] = data_get($data, 'totalAvg');
			
			$export[]= $row;
		}
		
		return $export;
	}
}
