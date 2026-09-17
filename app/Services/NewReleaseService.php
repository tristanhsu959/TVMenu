<?php

namespace App\Services;

use App\Facades\AppManager;
use App\Facades\PosManager;
use App\Facades\StoreManager;
use App\Repositories\NewReleaseRepository;
use App\Services\NewRelease\StoreService;
use App\Services\NewRelease\AreaService;
use App\Services\NewRelease\RankingService;
use App\Libraries\ResponseLib;
use App\Libraries\HelperLib;
use App\Enums\OpCenter;
use App\Enums\Brand;
use App\Enums\Functions;
use App\Enums\Area;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Fluent;
use Carbon\CarbonPeriod;
use Exception;
use OpenSpout\Writer\Common\Creator\WriterEntityFactory;
use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style; 
use OpenSpout\Common\Entity\Style\CellAlignment;

class NewReleaseService
{
	private $_statistics	= [];
	
	public function __construct(protected NewReleaseRepository $_repository)
	{
		$this->_statistics = [
			'brandId'		=> '', #export
			'brandCode'		=> '', 
			'startDate'		=> '', #Y-m-d
            'endDate'   	=> '',
			'area' 			=> [],
			'shop' 			=> [],
			'top' 			=> [],
			'last' 			=> [],
			'hasResult'		=> FALSE,
			'exportName'	=> '', #export
			'exportToken'	=> '', #export
		];
	}
	
	/* Parsing brand from url segment
	 * @params: string
	 * @return: string
	 */
	public function parsingBrand($segments)
	{
		$brand = $segments[0];
		return Brand::tryFromCode($brand);
	}
	
	/* Parsing function by brand
	 * @params: enums
	 * @return: string
	 */
	public function parsingFunction($brand)
	{
		return match ($brand) 
		{
			Brand::BAFANG	=> Functions::BF_NEW_RELEASE, 
			Brand::BUYGOOD	=> Functions::BG_NEW_RELEASE,
        };
	}
	
	/* 取新品設定by brand
	 * @params: int
	 * @return: string
	 */
	public function getNewReleaseProducts($brandId)
	{
		$result = $this->_repository->getNewReleaseProducts($brandId);
		$result = collect($result)->keyBy('id')->all();
		
		return $result;
	}
	
	/* ====================== 主流程 ====================== */
	/* Search data
	 * @params: enum
	 * @params: int
	 * @params: date
	 * @params: date
	 * @return: array
	 */
	public function getStatistics($brand, $searchReleaseId, $searchStDate, $searchEndDate, $searchAreaIds)
	{
		try
		{
			if (AppManager::hasAreaPermission() === FALSE)
				return ResponseLib::initialize($this->_statistics)->fail('此使用者無區域瀏覽權限');
			
			#Params都用pass(保留service可複用空間)
			$params = $this->_initParams($brand, $searchReleaseId, $searchStDate, $searchEndDate, $searchAreaIds);
			
			if (Cache::has($params->cacheKey))
			{
				Log::channel('appServiceLog')->info('Get new release data from cache');
				
				$statistics = Cache::get($params->cacheKey); #cache data is response format
				return ResponseLib::initialize($statistics)->success();
			}
			else
			{
				Log::channel('appServiceLog')->info('Get new release data from db');
				
				#Prepare data(object default called by reference)
				$this->_prepareData($params);
				
				#Statistics
				$this->_outputReport($params);
				
				#Create output to var statistics
				$this->_generateStatistics($params);
				
				return ResponseLib::initialize($this->_statistics)->success();
			}
		}
		catch(Exception $e)
		{
			return ResponseLib::initialize($this->_statistics)->fail($e->getMessage());
		}
	}
	
	/* Init input params
	 * @params: enums
	 * @params: integer
	 * @params: string
	 * @params: string
	 * @return: array
	 */
	private function _initParams($brand, $searchReleaseId, $searchStDate, $searchEndDate, $searchAreaIds)
	{
		$params = new Fluent();
		
		#Op & Area有權限設定,故要再與查詢條件判別
		#銷售取全部營運中心, 因取門店仍需要此參數
		$allowOpCenterIds	= PosManager::getAllowOpCenters(); 
		$allowAreaIds		= PosManager::getAllowAreas($searchAreaIds); #整併查詢參數
		
		$searchEndDate 	= empty($searchEndDate) ? now()->format('Y-m-d') : $searchEndDate;
		$functions 		= $this->parsingFunction($brand);
		$cacheKey 		= HelperLib::buildCacheKey([$functions->value, $allowOpCenterIds, $allowAreaIds, $searchReleaseId, $searchStDate, $searchEndDate]);
		
		$params->brand($brand)->allowOpCenterIds($allowOpCenterIds)->allowAreaIds($allowAreaIds)
				->releaseId($searchReleaseId)->stDate($searchStDate)->endDate($searchEndDate)
				->cacheKey($cacheKey);
		
		return $params;
	}
	
	/* Generate statistics data
	 * @params: object
	 * @return: array
	 */
	private function _generateStatistics($params)
	{
		$this->_statistics['brandId']		= $params->brand->value;
		$this->_statistics['brandCode']		= $params->brand->code();
		$this->_statistics['startDate'] 	= $params->stDate;
		$this->_statistics['endDate']		= $params->endDate;
		$this->_statistics['shop']			= $params->shop;
		$this->_statistics['area']			= $params->area;
		$this->_statistics['top']			= $params->top;
		$this->_statistics['last']			= $params->last;
		$this->_statistics['hasResult']		= FALSE;
		$this->_statistics['exportName']	= $params->productName;
		$this->_statistics['exportToken']	= '';
		
		#無值不cache
		if (! empty($this->_statistics['area']['data']))
		{
			$this->_statistics['hasResult']		= TRUE;
			$this->_statistics['exportToken'] 	= bin2hex($params->cacheKey); #hex2bin
			Cache::put($params->cacheKey, $this->_statistics, now()->addMinutes(10));
		}
	}
	
	/* 取新品銷售統計相關資料
	 * @params: enums
	 * @params: integer
	 * @return: array
	 */
	private function _prepareData($params)
	{
		try
		{
			#1.計算查詢範圍總天數 (use Date not DateTime)
			$this->_buildDayRange($params);
			
			#2. Get product params
			$this->_getProductParams($params);
			
			#3. Get all shops with area permission
			$this->_getStoreList($params);
			
			#4. Build base data
			#會有false的無效array, 用array_filter去除
			$this->_getDataFromDB($params);
			
			#5. Build base data
			#會有false的無效array, 用array_filter去除
			$this->_buildBaseData($params);
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception($e->getMessage());
		}
	}
	/* ====================== 主流程 End ====================== */
	
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
		
		$params->dayRange	= $dateList;
		$params->totalDays 	= count($dateList);
	}
	
	/* 取ErpNo及條件
	 * @params: object
	 * @return: array
	 */
	private function _getProductParams($params)
	{
		try
		{
			#取新品設定參數
			$settings = $this->_repository->getSettingById($params->releaseId);
			
			if (empty($settings))
				throw new Exception('新品設定不存在或已停用');
			
			#取出對應的erpNo料號
			$result = $this->_repository->getErpNoById($params->releaseId);
			
			#分開primary & secondary
			$primaryIds = collect($result)->filter(function($item, $key){
				return $item['isPrimary'];
			})->pluck('erpNo')->toArray();
			
			$secondaryIds = collect($result)->filter(function($item, $key){
				return ! $item['isPrimary'];
			})->pluck('erpNo')->toArray();
			
			$params->productName	= $settings['releaseName'];
			$params->primaryIds		= $primaryIds;
			$params->secondaryIds 	= $secondaryIds;
			$params->taste 			= $settings['releaseTaste'];
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('解析查詢參數發生錯誤');
		}
	}
	
	/* 門店資料
	 * @params: collection
	 * @return: array
	 */
	private function _getStoreList($params)
	{
		#改Mapping訂貨門店-已判別日期, 等同active list
		$brand 				= $params->brand;
		$allowOpCenterIds 	= $params->allowOpCenterIds;
		$allowAreaIds 		= $params->allowAreaIds;
		$stDate				= $params->stDate;
		$endDate			= $params->endDate;
		
		#新品先過濾廠區學區店
		$storeList = StoreManager::getStoreList($brand, $allowOpCenterIds, $allowAreaIds, $stDate, $endDate);
		$storeList = PosManager::filterSpecialStore($brand, $storeList);
		$params->storeList = StoreManager::filterFactoryStore($brand, $storeList);
	}
	
	/* Get main data & mapping data
	 * @params: date
	 * @params: date
	 * @params: array
	 * @params: array => product ids of BF
	 * @return: array
	 */
	private function _getDataFromDB($params)
	{
		try
		{
			$brand 			= $params->brand;
			$stDate			= (new Carbon($params->stDate))->format('Y-m-d 00:00:00');
			$endDate 		= (new Carbon($params->endDate))->addDay()->format('Y-m-d H:i:s');
			$primaryIds 	= $params->primaryIds;
			$secondaryIds 	= $params->secondaryIds;
			$tastes 		= $params->tastes;
			$allowAreaIds 	= $params->allowAreaIds;
			
			$saleData = $this->_repository->getSaleData($brand, $stDate, $endDate, $primaryIds, $secondaryIds, $tastes, $allowAreaIds);
				
			$params->saleData = $saleData;
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('讀取POS系統訂單資料失敗');
		}
	}
	
	/* 基底資料
	 * @params: collection
	 * @return: array
	 */
	private function _buildBaseData($params)
	{
		/*
		[
		330002 => [
			"shopId" => "350001"
			"saleDate" => "2025-09-22"
			"qty" => "4"
			"storeName" => "御廚竹南博愛店"
			"areaId" => 3
			"areaName" => "桃竹苗區"
		]
		*/
		$saleData = array_filter($params->saleData);
		
		if (empty($saleData))
		{
			$params->baseData = [];
			return;
		}
		
		#DB有濾一遍了,只是預防萬一
		$saleData = PosManager::filterDataByExceptStore($params->brand, $saleData);
		
		#整理saleData
		$saleData = collect($saleData)->map(function($item, $key) {
			$item['saleDate'] = Carbon::parse($item['saleDate'])->format('Y-m-d');
			return $item;
		})->groupBy('shopId')->toArray();
		
		#所有有效店家統計(因門店已是有效全部門店,改寫成以門店取資料)
		#gid在pos manager已處理成統一area id
		#這裏還不會Parsing
		$baseData = collect($params->storeList)->map(function($item, $key) use($params, $saleData) {
			
			$data = data_get($saleData, $item['posId'], NULL); 
			
			#無資料或無POS Id的店
			if (empty($data))
				$item['data'] = [];
			else
			{
				$item['data'] = collect($data)->mapWithKeys(function($item, $key){
					return [$item['saleDate'] => $item['qty']];
				})->toArray();
			}
			
			return $item; 
		})->reject(function($item, $key){
			return empty($item);
		})->toArray();
		
		$params->baseData = $baseData;
	}
	
	/* 基底資料
	 * @params: collection
	 * @return: array
	 */
	/* private function _buildBaseData($params)
	{
		/*
		[
		330002 => [
			"shopId" => "350001"
			"saleDate" => "2025-09-22"
			"qty" => "4"
			"storeName" => "御廚竹南博愛店"
			"areaId" => 3
			"areaName" => "桃竹苗區"
		]
		*#/
		$saleData = array_filter($params->saleData);
		
		if (empty($saleData))
		{
			$params->baseData = [];
			return;
		}
		
		#所有有效店家統計
		$storeList = collect($params->storeList)->mapWithKeys(function($item, $key){
			return [$item['posId'] => $item]; #posId
		});
		
		#DB有濾一遍了,只是預防萬一
		$saleData = PosManager::filterDataByExceptStore($params->brand, $saleData);
		
		#gid在pos manager已處理成統一area id
		$baseData = collect($saleData)->map(function($item, $key) use($storeList) {
			
			$store	= data_get($storeList, $item['shopId'], NULL); 
			#$shop 	= data_get($saleStoreList, $item['shopId'], NULL);
			
			#對應不到表示無權限
			if (is_null($store))
				return '';
			
			$item['saleDate']	= Carbon::parse($item['saleDate'])->format('Y-m-d');
			$item['storeName'] 	= $store['storeName'];
			$item['areaId'] 	= $store['areaId'];
			$item['areaName']	= $store['areaName'];
			$item['storeKey'] 	= $store['storeKey'];
			
			/*
			$item['saleDate']	= Carbon::parse($item['saleDate'])->format('Y-m-d');
			$item['storeName'] 	= data_get($store, 'storeName', empty($shop) ? 'UNKNOW' :  $shop['storeName']);
			$item['areaId'] 	= data_get($store, 'areaId', empty($shop) ? 0 :  $shop['areaId']);
			$item['areaName']	= data_get($store, 'areaName', empty($shop) ? 'UNKNOW' :  $shop['areaName']);
			$item['storeKey']	= data_get($store, 'storeKey', empty($shop) ? '' :  $shop['storeKey']);
			*#/
			
			return $item; 
		})->reject(function($item, $key){
			return empty($item);
		});
		
		#補全未有銷售的門店資料(只需補active store)
		$saleShopIds = $baseData->pluck('shopId')->unique()->values()->toArray();
		$filloutShops = StoreManager::filterStoreByPosId($storeList, $saleShopIds);
		
		#重建
		$filloutShops = collect($filloutShops)->map(function($item, $key) use($params){
			$temp['shopId'] 	= $item['posId'];
			$temp['saleDate'] 	= $params->endDate;
			$temp['qty'] 		= 0;
			$temp['storeName'] 	= $item['storeName'];
			$temp['areaId'] 	= $item['areaId'];
			$temp['areaName']	= $item['areaName'];
			$temp['storeKey']	= $item['storeKey'];
			
			return $temp;
		});
		
		$params->baseData = $baseData->merge($filloutShops)->toArray();
	} */
	
	
	/* ========================== 統計 ========================== */
	/* 取使用者可讀取區域資料(原主邏輯不動)
	 * @params: fluent object
	 * @return: array
	 */
	private function _outputReport($params)
	{
		try
		{
			#1.區域彙總
			$areaService = app(AreaService::class);
			$areaService->parsing($params);
			
			#2.店別每日銷售
			$storeService = app(StoreService::class);
			$storeService->parsing($params);
						
			#4.當日銷售前10名
			#5.當日銷售後10名
			$rankingService = app(RankingService::class);
			$rankingService->parsing($params);
			
			return $params;
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('解析報表資料發生錯誤');
		}
	}
	
	/* Export data
	 * @params: enum
	 * @params: date
	 * @params: date
	 * @return: array
	 */
	public function export($token)
	{
		#取資料邏輯共用
		$cacheKey = hex2bin($token);
		
		if (! Cache::has($cacheKey))
			return ResponseLib::initialize()->fail('資料已過期，請重新查詢後下載'); #暫不做重查的動作
		
		$currentUser = AppManager::getCurrentUser();
		Log::channel('appServiceLog')->info(Str::replaceArray('?', [$currentUser->getAvailableName(), $cacheKey], '[?]Export new release data-?'));
		
		try
		{
			$sourceData = Cache::get($cacheKey);
			
			$areaService	= app(AreaService::class);
			$storeService 	= app(StoreService::class);
			$rankingService = app(RankingService::class);
			
			#Build export data for sheets
			$export['區域彙總'] 		= $areaService->buildExport($sourceData['area']);
			$export['店別明細'] 		= $storeService->buildExport($sourceData['shop']);
			$export['當日銷售前10名'] = $rankingService->buildExport($sourceData['top'], $sourceData['endDate']);
			$export['當日銷售後10名']	= $rankingService->buildExport($sourceData['last'], $sourceData['endDate']);
			
			#Write export to file
#			$fileName = Str::replace(':', '_', $cacheKey); 
			$brandName = Brand::tryFrom($sourceData['brandId'])->label();
			$fileName = Str::replaceArray('?', [$brandName, $sourceData['exportName'], $sourceData['startDate'], $sourceData['endDate']], '?_新品_?_?_?.xlsx');
			$filePath = Storage::disk('export')->path($fileName);
			
			$writer = new Writer();
			#$writer->openToBrowser($fileName);
			$writer->openToFile($filePath);
			
			$centerStyle = (new Style())->setCellAlignment(CellAlignment::CENTER);
				
			foreach($export as $sheetName => $sheetData)
			{
				$sheet = ($sheetName == '區域彙總') ? $writer->getCurrentSheet() : $writer->addNewSheetAndMakeItCurrent();
				$sheet->setName($sheetName);
				
				foreach($sheetData as $data)
				{
					$row =  Row::fromValues($data, $centerStyle);
					$writer->addRow($row);
				}
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
