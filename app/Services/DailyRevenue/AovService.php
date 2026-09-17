<?php

namespace App\Services\DailyRevenue;

use App\Facades\AppManager;
use App\Facades\PosManager;
use App\Repositories\DailyRevenueRepository;
use App\Libraries\ResponseLib;
use App\Libraries\Sales\AreaLib;
use App\Enums\Brand;
use App\Enums\Functions;
use App\Enums\Area;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Number;
use Carbon\CarbonPeriod;
use Exception;
use OpenSpout\Writer\Common\Creator\WriterEntityFactory;
use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;

#partial Service
class AovService
{
	private $_statistics	= [];
   
	public function __construct(protected DailyRevenueRepository $_repository)
	{
		$this->_statistics = [
			'brandId'		=> '', #export
			'brandCode'		=> '',
			'type'			=> '',
			'startDate'		=> '', #Y-m-d
			'data' 			=> [],
			'exportName'	=> '',
			'exportToken'	=> '', #export
		];
	}
	
	/* ====================== 主流程 By Name ====================== */
	/* Search data
	 * @params: array
	 * @return: array
	 */
	public function analysis($params)
	{
		try
		{
			$this->_prepareData($params);
			
			$this->_outputReport($params);
		
			$this->_generateStatistics($params);
			
			return $this->_statistics;
		}
		catch(Exception $e)
		{
			throw new Exception($e->getMessage());
		}
	}
	
	/* Generate statistics data
	 * @params: object
	 * @return: array
	 */
	private function _generateStatistics($params)
	{
		$this->_statistics['brandId']		= $params->brand->value;
		$this->_statistics['brandCode']		= $params->brand->code();
		$this->_statistics['type']			= $params->type;
		$this->_statistics['startDate'] 	= $params->stDate;
		$this->_statistics['data']			= $params->data;
		$this->_statistics['exportName']	= '';
		$this->_statistics['exportToken']	= '';
		$this->_statistics['hasResult']		= FALSE;
		
		#無值不cache
		if (! empty(Arr::flatten($this->_statistics['data']['total'])))
		{
			$this->_statistics['hasResult']		= TRUE;
			$this->_statistics['exportToken'] 	= bin2hex($params->cacheKey); #hex2bin
			Cache::put($params->cacheKey, $this->_statistics, now()->addMinutes(20));
		}
	}
	
	/* ====================== 主流程 End ====================== */
	
	/* Get search data
	 * @params: array
	 * @return: array
	 */
	private function _prepareData($params)
	{
		try
		{
			#以order的店數來計算,不用取店,因也無法判別open or close
			#1. Get data from DB
			$this->_getDataFromDB($params);
			
			#2.build to base data
			$this->_buildBaseData($params);
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception($e->getMessage());
		}
	}
	
	/* Get buy good data
	 * @params: fluent
	 * @return: array
	 */
	private function _getDataFromDB($params)
	{
		try
		{
			$brand 			= $params->brand;
			$stDate			= (new Carbon($params->stDate))->format('Y-m-d 00:00:00');
			$endDate 		= (new Carbon($params->endDate))->addMonth()->format('Y-m-d H:i:s');
			$storeType 		= $params->storeType;
			$allowAreaIds 	= $params->allowAreaIds;
			
			$result = $this->_repository->getDataByAverageOrderValue($brand, $allowAreaIds, $stDate, $endDate, $storeType);
			
			$params->saleData = array_filter($result);
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('讀取POS系統訂單資料失敗');
		}
	}
	
	/* 基底資料(DB已計算Sum)
	 * @params: collection
	 * @return: array
	 */
	private function _buildBaseData($params)
	{
		/*
		0 => array:6 [
			"saleMonth" => "2026-06"
			"shopKind" => "2"
			"areaId" => 1
			"areaName" => "大台北區"
			"visitors" => 3609
			"amount" => 610476.0
		]
		*/
		
		#再過濾一次, 這裏無法補全門店
		#$saleData = PosManager::filterDataByExceptStore($params->brand, $saleData);
		
		$saleData = $params->saleData;
		
		#先處理基本的計算
		$baseData = collect($saleData)->map(function($item, $key){
			
			#因amount有可能是0
			$totalSales = round(floatval($item['totalSales']) + floatval($item['totalExtra']) + floatval($item['totalDischarge']), 2); #不含折讓 
			$amount		= round($item['amount'], 2);
			
			$temp['saleMonth'] 		= Carbon::parse($item['saleDate'])->format('Y-m');
			$temp['storeCount'] 	= intval($item['shopCount']);
			$temp['storeType'] 		= $item['shopKind'];
			$temp['areaId'] 		= AreaLib::toId($item['areaId']);
			$temp['areaName']		= (Area::tryFrom($temp['areaId']))->label();
			$temp['visitors'] 		= intval($item['visitors']);
			$temp['amount']			= empty($totalSales) ? $amount : $totalSales;
			
			return $temp;
		})->toArray();
		
		$params->baseData = $baseData;
	}
	
	/* ========================== 統計 ========================== */
	/* ========================================================== */
	/* 取使用者可讀取區域資料(原主邏輯不動)
	 * @params: array
	 * @return: array
	 */
	private function _outputReport($params)
	{
		try
		{
			#1.Parsing by store type(直營或加盟)
			$this->_parsingByStoreType($params);
			
			return $params;
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('解析報表資料發生錯誤');
		}
	}
	
	/* 客單統計
	 * @params: array
	 * @return: array
	 */
	private function _parsingByStoreType($params)
	{
		/*
		"typeId" => [
			areaId => array:6 [
				"saleMonth" => "2026-06"
				"shopKind" => "2"
				"areaId" => 1
				"areaName" => "大台北區"
				"visitors" => 3609
				"amount" => 610476.0
			]
		]
		*/
		
		$params->set('data.storeType', config('web.sales.shop.type'));
		$params->set('data.header', []);
		$params->set('data.total', []); #全區總計
		$params->set('data.subTotal', []); #分區總計
		
		$baseData = $params->baseData;
		
		#會有無設定區域權限的狀況, 須判別
		if (empty($baseData))
			return [];
		
		$params->set('data.header', ['年/月', '區域', '門店數', '合計營收', '店均營收', '總來客', '店均來客', '客單價']);
		
		#全區總計By shop kind
		$total = collect($baseData)->groupBy('storeType')->map(function($items, $key) {
			
			return $items->groupBy('saleMonth')->map(function($items, $key){
				$data['saleMonth'] 		= $items->pluck('saleMonth')->first();
				$data['areaName']		= '全區';
				$data['storeCount']		= $items->pluck('storeCount')->sum(); #店家數
				$data['amount']			= $items->pluck('amount')->sum(); #合計營收
				$data['avgStoreAmount']	= round(floatval($data['amount']) / intval($data['storeCount']), 2); #店均營收
				$data['visitors']		= $items->pluck('visitors')->sum(); #總來客
				$data['avgVisitors']	= empty($data['storeCount']) ? 0 : round($data['visitors'] / $data['storeCount'], 2); #店均來客
				$data['avgOrderValue']	= empty($data['visitors']) ? 0 : round($data['amount'] / $data['visitors'], 2); #客單價
					
				return $data;
			})->sortKeys();

		})->sortKeys()->toArray();
		
		#分區總計By shop kind
		$subTotal = collect($baseData)->groupBy('storeType')->map(function($items, $key) {
			
			return $items->groupBy('saleMonth')->map(function($items, $key){
				return $items->groupBy('areaId')->map(function($items, $key){
					$data['saleMonth'] 		= $items->pluck('saleMonth')->first();
					$data['areaName']		= $items->pluck('areaName')->first();
					$data['storeCount']		= $items->pluck('storeCount')->sum(); #店家數
					$data['amount']			= $items->pluck('amount')->sum();
					$data['avgStoreAmount']	= empty($data['storeCount']) ? 0 : round(floatval($data['amount']) / intval($data['storeCount']), 2); #店均營收
					$data['visitors']		= $items->pluck('visitors')->sum(); #總來客
					$data['avgVisitors']	= empty($data['storeCount']) ? 0 : round($data['visitors'] / $data['storeCount'], 2); #店均來客
					$data['avgOrderValue']	= empty($data['visitors']) ? 0 : round($data['amount'] / $data['visitors'], 2); #客單價
					
					return $data;
				})->sortKeys()->values();
			})->sortKeys();
			
		})->sortKeys()->toArray();
		
		$params->set('data.total', $total);
		$params->set('data.subTotal', $subTotal);
	}
	
	/*************** 匯出 ***************/
	/* Export data
	 * @params: array
	 * @return: array
	 */
	public function export($sourceData)
	{
		try
		{
			#Build export data for sheets
			#目前只下載total
			foreach($sourceData['data']['storeType'] as $typeKey => $typeName)
			{
				$exportData = data_get($sourceData, "data.total.{$typeKey}", NULL);
				
				if (empty($exportData))
					continue;
				
				$export[$typeName] = $this->_buildExportData($sourceData['data']['header'], $sourceData['data']['total'][$typeKey]);
            }
			
			#Write export to file
			$brandName = Brand::tryFrom($sourceData['brandId'])->label();
			$fileName = Str::replaceArray('?', [$brandName, $sourceData['startDate']], '?_客單統計_?.xlsx');
			$filePath = Storage::disk('export')->path($fileName);
			
			$writer = new Writer();
			$writer->openToFile($filePath);
			
			$index = 0;
			foreach($export as $sheetName => $sheetData)
			{
				$sheet = ($index == 0) ? $writer->getCurrentSheet() : $writer->addNewSheetAndMakeItCurrent();
				$sheet->setName($sheetName);
				
				foreach($sheetData as $data)
				{
					$row =  Row::fromValues($data);
					$writer->addRow($row);
				}
				$index++;
			}
			
			$writer->close();
			return ResponseLib::initialize($fileName)->success();
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			return ResponseLib::initialize('檔案下載失敗，請重新查詢')->fail();
		}
	}
	
	/* Build data for export
	 * @params: array
	 * @return: array
	 */
	private function _buildExportData($header, $srcData)
	{
		$export = [];
		$export[] = $header;
		
		#statistics data
		if (empty($srcData))
			return $export;
		
		foreach($srcData as $month => $data)
		{
			$row = [];
			$row[] = $data['saleMonth'];
			$row[] = $data['areaName'];
			$row[] = $data['storeCount']; #店家數
			$row[] = Number::currency($data['amount'], precision: 0);
			$row[] = Number::currency($data['avgStoreAmount'], precision: 0); #店均營收
			$row[] = $data['visitors']; #總來客
			$row[] = $data['avgVisitors']; #店均來客
			$row[] = Number::currency($data['avgOrderValue'], precision: 0); #客單價
				
			$export[] = $row;
		}
		
		return $export;
	}
}
