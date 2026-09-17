<?php

namespace App\Services\Merchant;

use App\Facades\AppManager;
use App\Facades\PurchaseManager;
use App\Facades\StoreManager;
use App\Repositories\MerchantRepository;
use App\Libraries\ResponseLib;
use App\Libraries\Purchase\AreaLib;
use App\Enums\Brand;
use App\Enums\Functions;
use App\Enums\Area;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Number;
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
class DayoffService
{
	public function __construct(protected MerchantRepository $_repository)
	{
		
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
		$statistics['type']			= $params->type;
		$statistics['startDate'] 	= $params->stDate; 
		$statistics['areaDayoff'] 	= $params->areaDayoff;
		$statistics['dayoff'] 		= $params->dayoff;
		$statistics['hasResult'] 	= FALSE;
		
		#無值不cache
		if (! empty($params->areaDayoff['store']))
		{
			$statistics['hasResult'] 	= TRUE;
			$statistics['dayoffCount']	= collect($params->areaDayoff['store'])->pluck('dayoffCount')->sum();
			$statistics['exportToken'] 	= bin2hex($params->cacheKey); #hex2bin
			$statistics['exportName'] 	= '店休資訊';
			
			Cache::put($params->cacheKey, $statistics, now()->addMinutes(10));
		}
		
		return $statistics;
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
			$this->_getStoreList($params);
			
			$this->_getProductId($params);
			
			$this->_getDataFromDB($params);
			
			$this->_buildBaseData($params);
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception($e->getMessage());
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
			
		#店休改為不顯示蘿蔔(但資料有抓蘿蔔)
		$storeList = StoreManager::getStoreList($brand, $allowOpCenterIds, $allowAreaIds, $stDate, $endDate);
		#$storeList = StoreManager::filterFactoryStore($brand, $storeList);
		
		$params->storeList = $storeList;
	}
	
	/* 以short code取得product id
	 * @params: array
	 * @return: array
	 */
	private function _getProductId($params)
	{
		try
		{
			#取資料都不分營運中心,不然可能會取不到
			$brand				= $params->brand;
			$allowOpCenterIds 	= $params->allowOpCenterIds;
			
			$brandId			= $brand->value;
			$shortCodes			= config("web.purchase.product_type.dayOff.{$brandId}");
			
			$productIds = PurchaseManager::getProductIdByShortCode($brand, $allowOpCenterIds, $shortCodes);
			
			if (empty($productIds))
				throw new Exception('查無產品代碼');
			
			$params->productIds = $productIds;
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
	private function _getDataFromDB($params)
	{
		/*0 => array:11 [
			"areaId" => "21"
			"storeId" => "156"
			"storeNo" => "KH4000002"
			"storeName" => "台中柳川店"
			"posId" => "0388"
			"money" => 11
		]
		*/
		
		#改為判別八方：招韭辣餡/御廚：炸雞腿/炸排骨
		try
		{
			$brand 				= $params->brand;
			$stDate				= Carbon::parse($params->stDate)->format('Y-m-d 00:00:00');
			$endDate 			= Carbon::parse($stDate)->addDay()->format('Y-m-d H:i:s');
			$allowOpCenterIds 	= $params->allowOpCenterIds;
			$allowAreaIds		= $params->allowAreaIds;
			$productIds			= $params->productIds;
			
			$result = $this->_repository->getDayoffList($brand, $allowOpCenterIds, $allowAreaIds, $stDate, $endDate, $productIds);
			
			$params->orderData = $result;
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('讀取店休資料失敗');
		}
	}
	
	/* 基底資料
	 * @params: collection
	 * @return: array
	 */
	private function _buildBaseData($params)
	{
		#有佔比只是暫時不顯示, 故要取全門店
		$orderData = collect($params->orderData)->map(function($item, $key){
			$item['storeKey'] = StoreManager::buildStoreKey($item['storeNo']);
			return $item;
		})->groupBy('storeKey')->map(function($items, $key){
			#因不顯示產品,故可不用ProductName
			return collect($items)->groupBy('shortCode')->map(function($items, $key){
				return $items->pluck('qty')->sum();
			})->all();
		})->all();
		
		$baseData = collect($params->storeList)->map(function($item, $key) use($orderData){
			$data = data_get($orderData, $item['storeKey'], []);
			
			$temp['storeKey']	= $item['storeKey'];
			$temp['areaId'] 	= $item['areaId'];
			$temp['areaName'] 	= $item['areaName'];
			$temp['posId'] 		= $item['posId'];
			$temp['storeName'] 	= $item['storeName'];
			$temp['hasDayOff']	= (collect($data)->sum() <= 0) ? 1 : 0; #方便計算
			
			return $temp;
		})->toArray();
		
		$params->baseData = $baseData;
	}
	
	/* ========================== 統計 ========================== */
	/* ========================================================== */
	/* Build report
	 * @params: array
	 * @params: array
	 * @return: array
	 */
	private function _outputReport($params)
	{
		try
		{
			#1.Build area statistics
			$this->_buildDayoffByArea($params);
			
			#2.Build store info
			$this->_buildDayoffByDetail($params);
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('解析報表資料發生錯誤');
		}
	}
	
	/* Build area dayoff stores
	 * @params: array
	 * @return: array
	 */
	private function _buildDayoffByArea($params)
	{
		try
		{
			$baseData = $params->baseData;
			
			#Statistics dayoff
			#先過濾無訂貨再分區域
			$dayoffData = collect($baseData)->groupBy('areaId')->map(function($items, $key) {
				
				$temp['areaId']		= $items->pluck('areaId')->first();
				$temp['areaName']	= $items->pluck('areaName')->first();
				$temp['total']		= $items->count(); #門店數
				$temp['dayoffCount']= $items->pluck('hasDayOff')->sum();
				
				#佔不提供佔比(上頭指示)
				#$temp['percent']	= round(intval($temp['dayoffCount']) / intval($temp['total']) * 100, 2);
				return $temp;
			});
			
			$summary['areaId']		= '';
			$summary['areaName'] 	= '總計';
			$summary['total'] 		= $dayoffData->pluck('total')->sum();
			$summary['dayoffCount'] = $dayoffData->pluck('dayoffCount')->sum();
			#$summary['percent'] 	= round($dayoffData->pluck('percent')->sum() / $dayoffData->count(), 2);
			
			$area['header'] = ['區域', '店家數', '店休數']; /*['區域', '店家數', '店休數', '佔比'];*/
			$area['store'] = $dayoffData->merge([$summary])->toArray();
			
			$params->areaDayoff = $area;
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('統計區域店休資料失敗');
		}
	}
	
	/* Get order data
	 * @params: array
	 * @return: array
	 */
	private function _buildDayoffByDetail($params)
	{
		try
		{
			$baseData = $params->baseData;
			
			$dayoffDetail = collect($baseData)->filter(function($item, $key){
				return $item['hasDayOff'];
			})->map(function($item, $key) {
				
				#只取需要欄位
				$temp['areaId']		= $item['areaId'];
				$temp['areaName'] 	= $item['areaName'];
				$temp['posId'] 		= $item['posId'];
				$temp['storeKey']	= $item['storeKey'];
				$temp['storeName']	= $item['storeName'];
				
				return $temp;
			})->values()->all(); 
			
			$info['header'] = ['區域', 'Pos店號', '門店代號', '門店名稱'];
			$info['store']	= $dayoffDetail;
			
			$params->dayoff = $info;
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('統計門店店休資料失敗');
		}
	}
	
	/* Export data
	 * @params: array
	 * @return: array
	 */
	public function export($sourceData)
	{
		try
		{
			#Write export to file
			$brandName = Brand::tryFrom($sourceData['brandId'])->label();
			$fileName = Str::replaceArray('?', [$brandName, $sourceData['exportName'], $sourceData['startDate']], '?_?_?.xlsx');
			$filePath = Storage::disk('export')->path($fileName);
			
			$writer = new Writer();
			$writer->openToFile($filePath);
			
			#Build export data for sheets
			$exportArea = collect([$sourceData['areaDayoff']['header']])->merge($sourceData['areaDayoff']['store'])->toArray();
			$sheet = $writer->getCurrentSheet();
			$sheet->setName('店休-區域');
				
			foreach($exportArea as $key => $data)
			{
				unset($data['areaId']);
				$row =  Row::fromValues($data);
				$writer->addRow($row);
			}
			
			$exportStore = collect([$sourceData['dayoff']['header']])->merge($sourceData['dayoff']['store'])->toArray();
			$sheet = $writer->addNewSheetAndMakeItCurrent();
			$sheet->setName('店休-門店明細');
			
			foreach($exportStore as $key => $data)
			{
				unset($data['areaId']);
				$row =  Row::fromValues($data);
				$writer->addRow($row);
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
}
