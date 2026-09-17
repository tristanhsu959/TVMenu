<?php

namespace App\Services\PurchaseReport;

use App\Facades\AppManager;
use App\Facades\PurchaseManager;
use App\Facades\StoreManager;
use App\Facades\LegacyManager;
use App\Repositories\PurchaseReportRepository;
use App\Libraries\ResponseLib;
use App\Libraries\Purchase\AreaLib;
use App\Enums\Brand;
use App\Enums\Functions;
use App\Enums\Area;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;
use Carbon\CarbonPeriod;
use Exception;
use OpenSpout\Writer\Common\Creator\WriterEntityFactory;
use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;

#partial Service
class EmployeePrService
{
	private $_statistics	= [];
   
	public function __construct(protected PurchaseReportRepository $_repository)
	{
		$this->_statistics = [
			'type'		=> '',
			'brandId'		=> '', #export
			'brandCode'		=> '', 
			'startDate'		=> '', #Y-m-d
            'endDate'   	=> '',
			'employee'		=> [],
			'pr'			=> [],
			'exportName'	=> '', #export
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
		$this->_statistics['type']			= $params->type;
		$this->_statistics['brandId']		= $params->brand->value; 
		$this->_statistics['brandCode']		= $params->brand->code(); 
		$this->_statistics['startDate'] 	= $params->stDate; 
		$this->_statistics['endDate'] 		= $params->endDate;
		$this->_statistics['employee'] 		= $params->employee;
		$this->_statistics['pr'] 			= $params->pr;
		$this->_statistics['hasResult'] 	= FALSE;
		
		#無值不cache
		if (! empty($params->employee['data']) OR ! empty($params->pr['data']))
		{
			$this->_statistics['hasResult'] 	= TRUE;
			$this->_statistics['exportToken'] 	= bin2hex($params->cacheKey); #hex2bin
			
			$this->_statistics['exportName'] = '員購_公關';
			Cache::put($params->cacheKey, $this->_statistics, now()->addMinutes(10));
		}
	}
	
	/* ====================== 主流程 End ====================== */
	
	/* 取統計相關參數
	 * @params: enums
	 * @params: integer
	 * @return: array
	 */
	private function _prepareData($params)
	{
		try
		{
			#目前都在舊系統
			$this->_getEmployeeDataFromDB($params);
			
			$this->_getPRDataFromDB($params); 
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception($e->getMessage());
		}
	}
	
	/* Get order data
	 * @params: 
	 * @return: array
	 */
	private function _getEmployeeDataFromDB($params)
	{
		/*0 => [
			"orderNo" => "2026030810003"
			"orderDate" => "2026-03-08 09:30:04"
			"memo" => "嘉一香(已付)"
			"shortCode" => "0201"
			"productName" => "白豆漿"
			"unit" => "包"
			"price" => "40.00"
			"qty" => "3.00"
			"totalAmount" => "120.00"
			"factoryNo" => "TW_TP"
			"factoryName" => "淡水總廠"
		]
		*/
	
		try
		{
			$brand 				= $params->brand;
			$stDate				= (new Carbon($params->stDate))->format('Y-m-d 00:00:00');
			$endDate 			= (new Carbon($params->endDate))->addDay()->format('Y-m-d H:i:s');
			$allowOpCenterIds 	= $params->allowOpCenterIds;
			
			#舊系統只分工廠, 不分區域權限
			$factoryList = PurchaseManager::getFactoryList($brand, $allowOpCenterIds);
			$factoryNos = array_keys($factoryList);
			
			$orderData = LegacyManager::getEmployeeData($brand, $stDate, $endDate, $factoryNos);
			
			$params->employeeData = $orderData;
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('讀取公關員購訂單資料失敗');
		}
	}
	
	/* Get order data
	 * @params: 
	 * @return: array
	 */
	private function _getPRDataFromDB($params)
	{
		try
		{
			$brand 				= $params->brand;
			$stDate				= (new Carbon($params->stDate))->format('Y-m-d 00:00:00');
			$endDate 			= (new Carbon($params->endDate))->addDay()->format('Y-m-d H:i:s');
			$allowOpCenterIds 	= $params->allowOpCenterIds;
			
			#舊系統只分工廠, 不分區域權限
			$factoryList = PurchaseManager::getFactoryList($brand, $allowOpCenterIds);
			$factoryNos = array_keys($factoryList);
			
			$orderData = LegacyManager::getPRData($brand, $stDate, $endDate, $factoryNos);
			
			$params->prData = $orderData;
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('讀取公關員購訂單資料失敗');
		}
	}
	
	/* ========================== 統計 ========================== */
	/* ========================================================== */
	/* 
	 * @params: array
	 * @return: array
	 */
	private function _outputReport($params)
	{
		try
		{
			#1.員購/公關(目前無需特別處理或計算的欄位, 故先放在一起即可)
			$this->_parsing($params);
			
			return $params;
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('解析報表資料發生錯誤');
		}
	}
	
	/* Format output
	 * @params: array
	 * @return: array
	 */
	private function _parsing($params)
	{
		$header = ['訂單日期', '訂單編號', '產品名稱', '簡碼', '單位', '單價', '數量', '總金額', '出貨工廠', '備註'];
		
		$params->set('employee.header', $header);
		$params->set('employee.data', $this->_formatOutput($params->employeeData));
		$params->set('pr.header', $header);
		$params->set('pr.data', $this->_formatOutput($params->prData));
	}
	
	/* Format output
	 * @params: array
	 * @return: array
	 */
	private function _formatOutput($orderData)
	{
		#不同工廠會有相同單號
		$result = collect($orderData)->groupBy(function($item, $key){
			return "{$item['orderNo']}:{$item['factoryNo']}";
		})->map(function($items, $key){
			
			#order head
			$temp['orderDate']		= Carbon::parse($items->pluck('orderDate')->first())->format('Y-m-d');
			$temp['orderNo'] 		= $items->pluck('orderNo')->first();
			$temp['factoryName']	= $items->pluck('factoryName')->first();;
			$temp['totalAmount'] 	= $items->pluck('orderAmount')->first();
			$temp['productName']	= NULL;
			$temp['shortCode']		= NULL;
			$temp['unit']			= NULL;
			$temp['price']			= NULL;
			$temp['qty']			= NULL;
			#$temp['totalAmount']	= NULL;
			$temp['memo']			= NULL;
			
			
			$items = $items->map(function($item, $key){
				unset($item['orderAmount']);
				unset($item['factoryNo']);
				
				return $item;
			})->all();
			
			return collect([$temp])->merge($items)->all();
		})->values()->collapse()->all();
		
		return $result;
	}
	
	/* Export data
	 * @params: array
	 * @return: array
	 */
	public function export($sourceData)
	{
		try
		{
			#Build export data for sheets
			$export['員購'] = $this->_buildExportData($sourceData['employee']);
			$export['公關'] = $this->_buildExportData($sourceData['pr']);
			
			#Write export to file
			$brandName = Brand::tryFrom($sourceData['brandId'])->label();
			$fileName = Str::replaceArray('?', [$brandName, $sourceData['exportName'], $sourceData['startDate'], $sourceData['endDate']], '?_?_?_?.xlsx');
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
	private function _buildExportData($orderData)
	{
		$export = [];
		$export[] = $orderData['header'];
		
		#每個product要一個sheet
		foreach($orderData['data'] as $item)
		{
			$row = [];
			
			$row[]	= $item['orderDate'];
			$row[] 	= $item['orderNo'];
			$row[]	= $item['productName'];
			$row[]	= $item['shortCode'];
			$row[]	= $item['unit'];
			$row[]	= empty($item['price']) ? '' : Number::currency($item['price'], precision: 2);
			$row[]	= $item['qty'];
			$row[]	= empty($item['totalAmount']) ? '' : Number::currency($item['totalAmount'], precision: 2);
			$row[]	= $item['factoryName'];
			$row[]	= $item['memo'];
			
			$export[] = $row;
		}
		
		return $export;
	}
}
