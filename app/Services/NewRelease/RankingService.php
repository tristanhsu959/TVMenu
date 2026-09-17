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
class RankingService
{
	public function __construct()
	{
	}
	
	/* ====================== 主流程 By Name ====================== */
	/* 當日銷售前後10名
	 * @params: array
	 * @params: date
	 * @return: array
	 */
	public function parsing($params)
	{
		/* 以銷售量來group shop
		[
			"103001" => [
				"shopId" => "103001"
				"storeName" => "御廚民生承德直營店"
				"area" => "大台北區"
				"saleDate" => '2026-01-01'
				"qty" => 29
			]
		]
		*/
		
		$params->set('top', []);
		$params->set('last', []);
		
		$baseData	= $params->baseData;
		$endDate 	= $params->endDate;
		
		#會有無設定區域權限的狀況, 須判別
		if (empty($baseData))
			return FALSE;
		
		#排名是依最後一天的值(BaseData已是不重複門店)
		$result = collect($baseData)->map(function($item, $key) use($endDate) {
			
			$temp['areaName'] 	= $item['areaName'];
			$temp['storeName'] 	= $item['storeName'];
			$temp['shopId'] 	= $item['storeKey'];
			
			$dayData = collect($item['data'])->get($endDate, 0);
			$temp['qty'] = intval($dayData); 
			
			return $temp;
		});
		
		$top = $result->sortByDesc('qty')->groupBy('qty')->take(10)->values()->toArray();
		$last = $result->sortBy('qty')->groupBy('qty')->take(10)->values()->toArray();
		
		$params->set('top', $top);
		$params->set('last', $last);
	}
	
	/* Build data for export
	 * @params: array
	 * @return: array
	 */
	public function buildExport($rankingData, $targeDate)
	{
		$export[] = array_merge(['區域', '門店代號', '門店名稱'], [$targeDate], ['排名']);
		
		foreach($rankingData as $ranking => $shopList)
		{
			#同一排名會有重複
			foreach($shopList as $index => $data)
			{
				$row = [];
				$row[] = $data['areaName'];
				$row[] = $data['shopId'];
				$row[] = $data['storeName'];
				$row[] = $data['qty'];
				$row[] = $ranking + 1;
				
				$export[]= $row;
			}
		}
		
		return $export;
	}
}
