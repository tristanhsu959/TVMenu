<?php

namespace App\Services\SaleEvents;

use App\Facades\AppManager;
use App\Facades\PosManager;
use App\Facades\StoreManager;
use App\Repositories\SaleEventsRepository;
use App\Libraries\ResponseLib;
use App\Libraries\HelperLib;
use App\Enums\Brand;
use App\Enums\Functions;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Fluent;
use Illuminate\Support\Number;
use Illuminate\Support\Carbon;
use Carbon\CarbonPeriod;
use Exception;
use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style; 
use OpenSpout\Common\Entity\Style\CellAlignment;
use OpenSpout\Common\Entity\Style\CellVerticalAlignment;

#Partial service
class MoonFestivalService
{
	private $_statistics	= [];
	
	public function __construct(protected SaleEventsRepository $_repository)
	{
	}
	
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
			
			return $this->_generateStatistics($params);
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
		$statistics['brandId']		= $params->brand->value;
		$statistics['brandCode']	= $params->brand->code();
		$statistics['startDate'] 	= $params->stDate;
		$statistics['endDate']		= $params->endDate;
		$statistics['store']		= $params->store;
		$statistics['dayRange']		= $params->dayRange;
		$statistics['productList']	= $params->productList;
		$statistics['exportToken']	= '';
		$statistics['hasResult']	= FALSE;
		
		#無值不cache
		if (! empty($statistics['store']))
		{
			$statistics['hasResult']	= TRUE;
			$statistics['exportToken'] 	= bin2hex($params->cacheKey); #hex2bin
			Cache::put($params->cacheKey, $statistics, now()->addMinutes(10));
		}
		
		return $statistics;
	}
	
	/* ====================== Prepare data ====================== */
	/* Get search data
	 * @params: array
	 * @return: array
	 */
	private function _prepareData($params)
	{
		try
		{
			#1. Get product id list for sql
			$this->_getProductParams($params);
			
			#2. Get all shops with area permission
			$this->_getStoreList($params);
			
			#3.get data
			$this->_getDataFromDB($params);
			
			#6.build to base data
			$this->_buildBaseData($params);
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception($e->getMessage());
		}
	}
	
	
	
	/* 取ErpNo
	 * @params: eunums
	 * @return: array
	 */
	private function _getProductParams($params)
	{
		try
		{
			#此活動目前只有八方,邏輯由各自service處理,先不考慮共用狀況
			$brandId = $params->brand->value;
			$config = config("web.sales.events.{$brandId}.moonFestival");
			
			$comboId 	= $config['comboId']; #套餐主料號
			$productList= $config['productShortList']; #套餐對應的產品料號-名稱
			
			$params->comboId		= $comboId;
			$params->productList	= $productList; #erpNo=>productId list
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('解析產品參數發生錯誤');
		}
	}
	
	/* 門店資料
	 * @params: collection
	 * @return: array
	 */
	private function _getStoreList($params)
	{
		$brand 				= $params->brand;
		$allowOpCenterIds 	= $params->allowOpCenterIds;
		$allowAreaIds 		= $params->allowAreaIds;
		$stDate				= $params->stDate;
		$endDate			= $params->endDate;
		
		#不過濾廠區學區店
		$storeList = StoreManager::getStoreList($brand, $allowOpCenterIds, $allowAreaIds, $stDate, $endDate);
		$storeList = PosManager::filterSpecialStore($brand, $storeList);
		
		$params->storeList = $storeList;
	}
	
	/* Get sale data
	 * @params: fluent
	 * @return: array
	 */
	private function _getDataFromDB($params)
	{
		try
		{
			/* Return format */
			/*
			[
				"shopId" => "0030"
				"erpNo" => "PS00000001"
				"qty" => "20.0000"
				"saleDate" => "2026-08-27"
			]
			*/
			
			$brand 			= $params->brand;
			$stDate			= (new Carbon($params->stDate))->format('Y-m-d 00:00:00');
			$endDate 		= (new Carbon($params->endDate))->addDay()->format('Y-m-d H:i:s');
			$comboId 		= $params->comboId;
			$productIds 	= $params->productIds;
			$allowAreaIds 	= $params->allowAreaIds;
			
			$result = $this->_repository->getMoonFestivalSaleData($brand, $allowAreaIds, $stDate, $endDate, $comboId, $productIds);
			
			$params->saleData = $result;
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('讀取POS系統訂單資料失敗');
		}
	}
	
	
	/* Rebuild data format
	 * @params: Fluent
	 * @params: array
	 * @return: array
	 */
	private function _buildBaseData($params)
	{
		/* 重整資料格式/命名/區域
		array:11 [
			"shopId" => "100001"
			"storeName" => "御廚中正南昌店"
			"erpNo" => "UC00000042"
			"price_sum" => "360.0"
			"qty_sum" => "3"
			"areaId" => 1
			"areaName" => "大台北區"
			"productId" => 2
			"productName" => "橙汁排骨"
		]
		*/
		$saleData = array_filter($params->saleData);
		
		if (empty($saleData))
		{
			$params->baseData = [];
			return;
		}
		
		#例外門店如測試門店
		$saleData = PosManager::filterDataByExceptStore($params->brand, $saleData);
		
		$productList = $params->productList; 
		
		#DB有group了,同一天應不會有重複erpNo
		$saleData = collect($saleData)->filter(function($item, $key){
			#會有0的訂單
			return (intval($item['qty']) > 0);
		})->groupBy('shopId')->map(function($items, $key) use($productList){
			return collect($items)->map(function($item, $key) use($productList) {
				
				$temp['productId']	= $item['erpNo'];
				$temp['qty']		= intval($item['qty']);
				$temp['saleDate']	= $item['saleDate'];
				
				return $temp;
			})->toArray();
			
		})->toArray();
		
		#只留有資料的門店
		$baseData = collect($params->storeList)->map(function($item, $key) use($saleData) {
			
			$data = data_get($saleData, $item['posId'], []); 
			
			if (empty($data))
			{
				$temp['storeKey'] = NULL;
				return $temp;
			}
			
			$temp['storeKey'] 	= $item['storeKey'];
			$temp['storeId'] 	= $item['posId']; 
			$temp['storeName'] 	= $item['storeName'];
			$temp['areaId'] 	= $item['areaId'];
			$temp['areaName']	= $item['areaName'];
			$temp['products']	= $data;
			
			return $temp;
		})
		->reject(function($item, $key){
			return empty($item['storeKey']);
		})
		->values()->toArray();
		
		$params->baseData = $baseData;
	}
	
	/* ====================== Prepare data End ====================== */
	
	
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
			#1.Date list
			$this->_buildDayRange($params);
			
			#2.依店別
			$this->_parsingStore($params);
			
			return TRUE;
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('解析報表資料發生錯誤');
		}
	}
	
	/* 計算日期天數
	 * @params: 
	 * @return: array
	 */
	private function _buildDayRange($params)
	{
		$st 		= Carbon::create($params->stDate);
		$end 		= Carbon::create($params->endDate);
		$period 	= CarbonPeriod::create($st, $end);
		
		$dateList = [];

		foreach ($period as $date) 
		{
			$dateString = $date->format('Y-m-d');
			$dateList[$dateString] = $dateString;
		}
		
		$params->dayRange = $dateList;
	}
	
	/* 店別每日銷售
	 * @params: array
	 * @params: int
	 * @return: array
	 */
	private function _parsingStore($params)
	{
		/* 重整資料格式
		array:6 [
			"storeKey" => "2700005"
			"shopId" => "100001"
			"storeName" => "御廚中正南昌店"
			"areaId" => 1
			"areaName" => null
			"products" => [
				'2026-08-01' => array:2 [
				  "ProductId1" => 2
				  "ProductId2" => 90
				]
			]
		]
		*/
		$params->set('store', []);
		
		$baseData = $params->baseData;
		
		#會有無設定區域權限的狀況, 須判別
		if (empty($baseData))
			return FALSE;
		
		#header
		#$this->_buildStoreHeader($params);
		
		#Qty data
		$result = collect($baseData)->sortBy('areaId')->map(function($item, $key) {
			
			/* $temp['storeKey'] 	= $item['storeKey'];
			$temp['storeId'] 	= $item['storeId'];
			$temp['storeName'] 	= $item['storeName'];
			$temp['areaName'] 	= $item['areaName']; */
			
			$item['products'] = collect($item['products'])->groupBy('saleDate')->map(function($items, $key){
				
				return $items->mapWithKeys(function($item, $key){
					return [$item['productId'] => $item['qty']];
				});
				
			})->toArray();
			
			$item['totalQty'] = collect($item['products'])->collapse()->values()->sum();
			$item['totalUsed'] = $item['totalQty'] / 10; #兌換數
			
			unset($item['areaId']);
			
			return $item;	
		})->values()->all();
		
		$params->store = $result;
		#$params->set('store.data', $result);
	}
	
	/* 逐日銷售by store(暫不用)
	 * @params: array
	 * @params: int
	 * @return: array
	 */
	private function _buildStoreHeader($params)
	{
		$params->set('store.header', []);
		
		$headerDate = [
			'areaName'	=> '區域', 
			'storeId'	=> 'POS店號',
			'storeKey'	=> '門店代號', 
			'storeName'	=> '門店名稱',
		];
		
		$header = array_merge($headerDate, $params->dayRange);
		
		$params->set('store.header', $header);
	}
	
	/* Export data
	 * @params: enum
	 * @params: date
	 * @params: date
	 * @return: array
	 */
	public function export($sourceData)
	{
		try
		{
			#Build export data
			$header = $this->_buildExportHeader($sourceData);
			$export = $this->_buildExportData($sourceData);
			$export = array_merge($header, $export);
			
			#Write export to file
			$brandName = Brand::tryFrom($sourceData['brandId'])->label();
			$fileName = Str::replaceArray('?', [$sourceData['startDate'], $sourceData['endDate']], '八方X美廉社_中秋節活動_?_?.xlsx');
			$filePath = Storage::disk('export')->path($fileName);
			
			$writer = new Writer();
			$writer->openToFile($filePath);
			
			$sheet = $writer->getCurrentSheet();
			$sheet->setName('中秋節活動銷售統計');
				
			$centerStyle = (new Style())->setCellAlignment(CellAlignment::CENTER)
								->setCellVerticalAlignment(CellVerticalAlignment::CENTER);
			
			foreach($export as $data)
			{
				$row = Row::fromValues($data, $centerStyle);
				$writer->addRow($row);
			}
			
			#合併儲存格/最後一個 0 代表第一個工作表:mergeCells($topLeftColumn, $topLeftRow, $bottomRightColumn, $bottomRightRow, $sheetIndex = 0)
			$cols = count($header[0]) - 1;
			$writer->getOptions()->mergeCells(0, 1, 0, 2, 0);  
			$writer->getOptions()->mergeCells(1, 1, 1, 2, 0); 
			$writer->getOptions()->mergeCells(2, 1, 2, 2, 0); 
			$writer->getOptions()->mergeCells(3, 1, 3, 2, 0);
			$writer->getOptions()->mergeCells($cols-1, 1, $cols-1, 2, 0);
			$writer->getOptions()->mergeCells($cols, 1, $cols, 2, 0);
			
			$dateColSt = 4;
			$dayRange = array_values($sourceData['dayRange']);
			
			foreach($dayRange as $key => $date)
			{
				$st = $dateColSt + $key * 3;
				$writer->getOptions()->mergeCells($st, 1, $st+2, 1, 0);
			}
			#合併儲存格End
			
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
	private function _buildExportHeader($sourceData)
	{
		$export	= [];
		
		#header row1
		$header = ['區域', 'POS店號', '門店代號', '門店名稱'];
		
		#要處理合併儲存格預留欄位
		foreach($sourceData['dayRange'] as $date)
		{
			$header = array_merge($header, [$date, '', '']);
		}
		
		$header = array_merge($header, ['總計', '兌換數']);
		$export[] = $header;
		
		#header row2
		$header = ['', '', '', ''];
		$products = array_values($sourceData['productList']);
		
		foreach($sourceData['dayRange'] as $date)
		{
			$header = array_merge($header, $products);
		}
		
		$header = array_merge($header, ['']);
		$export[] = $header;
		
		return $export;
	}
	
	/* Build data for export
	 * @params: array
	 * @return: array
	 */
	private function _buildExportData($sourceData)
	{
		$export	= [];
		
		foreach($sourceData['store'] as $data)
		{
  			$row = [];
			
			$row[] = $data['areaName'];
			$row[] = $data['storeId'];
			$row[] = $data['storeKey'];
			$row[] = $data['storeName'];
			
			foreach($sourceData['dayRange'] as $date)
			{
				foreach($sourceData['productList'] as $productId => $productName)
				{
					$row[] = data_get($data, "products.{$date}.{$productId}", 0);
				}
			}
			
			$row[] = $data['totalQty'];
			$row[] = $data['totalUsed'];
			
			$export[] = $row;
		}
		
		return $export;
	}
}
