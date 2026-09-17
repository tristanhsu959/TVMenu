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
class AreaService
{
	public function __construct()
	{
	}
	
	/* ====================== 主流程 By Name ====================== */
	/* 區域彙總
	 * @params: array
	 * @params: int
	 * @return: array
	 */
	public function parsing($params)
	{
		/*
		"areaId" => [
			"大台北區" => [
				"shopCount" => 101
				"totalQty" => 22208
				"avgDayQty" => 965.6
				"avgShopQty" => 219.9
				"avgDayShopQty" => 9.6
			]
			"大高雄區" => array:5 []
			"宜蘭區" => array:5 []
			"中彰投區" => array:5 []
			"雲嘉南區" => array:5 []
			"桃竹苗區" => array:5 []
		]
		*/
		$params->set('area.header', []);
		$params->set('area.data', []);
		
		$baseData	= $params->baseData;
		$totalDays 	= $params->totalDays;
		
		#會有無設定區域權限的狀況, 須判別
		if (empty($baseData))
			return FALSE;
		
		$header = ['areaName' => '區域', 'storeCount'	=> '店家數', 'totalQty' => '銷售總量', 
					'avgDayQty' => '平均日銷售量', 'avgStoreQty' => '每店平均銷量', 'avgDayStoreQty' => '每店平均日銷量'];
		
		$params->set('area.header', $header);
		
		$result = collect($baseData)->groupBy('areaId')->map(function($items, $key) use($totalDays) {
			$data = $items->pluck('data')->filter()->flatten();
			
			$temp['areaName']		= $items->pluck('areaName')->first();
			$temp['storeCount']		= $items->pluck('storeId')->unique()->count(); #店家數
			$temp['totalQty'] 		= intval($data->sum()); #區域銷售總量
			$temp['avgDayQty'] 		= round($temp['totalQty'] / $totalDays, 1); 		#區域平均日銷售量: 區域銷售總量/天數
			$temp['avgStoreQty'] 	= round($temp['totalQty'] / $temp['storeCount'], 1); #區域每店平均銷量: 區域銷售總量/店家數
			$temp['avgDayStoreQty'] = round($temp['totalQty'] / $totalDays / $temp['storeCount'], 1); 	#區域每店平均日銷量: 區域銷售總量/店家數/天數
			
			return $temp;
		})->sortKeys()->toArray();
		
		#這裏是依header
		$result['total']['areaName'] 		= '全區合計'; 
		$result['total']['storeCount'] 		= collect($result)->pluck('storeCount')->sum(); 
		$result['total']['totalQty'] 		= collect($result)->pluck('totalQty')->sum();
		$result['total']['avgDayQty'] 		= round($result['total']['totalQty'] / $totalDays, 1);
		$result['total']['avgStoreQty'] 	= round($result['total']['totalQty'] / $result['total']['storeCount'], 1); #totalQty / shopCount
		$result['total']['avgDayStoreQty']	= round($result['total']['avgDayQty'] / $result['total']['storeCount'], 1); #avgDayQty / shopCount
		
		$params->set('area.data', $result);
	}
	
	/* Build data for export
	 * @params: array
	 * @return: array
	 */
	public function buildExport($areaData)
	{
		$header = $areaData['header'];
		$export[] = $header;
		
		foreach($areaData['data'] as $areaId => $data)
		{
			$row = [];
			
			foreach($header as $key => $headName)
			{
				$row[] = data_get($data, $key);
			}
			
			$export[]= $row;
		}
		
		return $export;
	}
}
