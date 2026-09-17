<?php

namespace App\Services;

use App\Services\DailyRevenue\DayService;
use App\Services\DailyRevenue\RangeService;
use App\Services\DailyRevenue\AovService;
use App\Facades\AppManager;
use App\Facades\PosManager;
use App\Repositories\DailyRevenueRepository;
use App\Enums\OpCenter;
use App\Enums\Brand;
use App\Enums\Functions;
use App\Enums\Area;
use App\Libraries\Sales\AreaLib;
use App\Libraries\HelperLib;
use App\Libraries\ResponseLib;
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

class DailyRevenueService
{
	private $_statistics	= [];
	
	public function __construct(protected DailyRevenueRepository $_repository)
	{
		$this->_statistics = [
			'brandId'		=> '', #export
			'type'			=> [],
			'calc'			=> [],
			'startDate'		=> '', #Y-m-d
            'endDate'   	=> '',
			'store' 		=> [],
			'area' 			=> [],
			'data'			=> [],
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
			Brand::BAFANG	=> Functions::BF_DAILY_REVENUE, 
			Brand::BUYGOOD	=> Functions::BG_DAILY_REVENUE,
			Brand::FJVEGGIE	=> Functions::FJ_DAILY_REVENUE,
        };
	}
	
	/* ====================== 主流程 ====================== */
	/* Search data
	 * @params: enum
	 * @params: date
	 * @params: date
	 * @params: array
	 * @params: string
	 * @return: array
	 */
	public function getStatistics($brand, $searchType, $searchCalc, $searchStDate, $searchEndDate, $searchStoreType, $searchAreaIds, $searchStoreName)
	{
		try
		{
			if (AppManager::hasAreaPermission() === FALSE)
				return ResponseLib::initialize($this->_statistics)->fail('此使用者無區域瀏覽權限');
			
			$params = $this->_initParams($brand, $searchType, $searchCalc, $searchStDate, $searchEndDate, $searchStoreType, $searchAreaIds, $searchStoreName);
			
			#主要是for即時，故每次都query
			if (Cache::has($params->cacheKey))
			{
				Log::channel('appServiceLog')->info('Get daily revenue from cache');
				
				$statistics = Cache::get($params->cacheKey); #cache data is response format
				
				return ResponseLib::initialize($statistics)->success();
			}
			else
			{
				Log::channel('appServiceLog')->info('Get daily revenue from db');
				
				if ($params->type == 'day') #門店/時段
					$service = app(DayService::class);
				else if ($params->type == 'range') #區域/門店
					$service = app(RangeService::class);
				else if ($params->type == 'aov') #By月合併,不顯示店
					$service = app(AovService::class);
				else
					throw new Exception('查詢門店營收時發生錯誤');
				
				#執行統計
				$this->_statistics = $service->analysis($params);
				
				return ResponseLib::initialize($this->_statistics)->success();
			}
		}
		catch(Exception $e)
		{
			return ResponseLib::initialize($this->_statistics)->fail($e->getMessage());
		}
	}
	
	/* ====================== 主流程 End ====================== */
	
	/* Init input params
	 * @params: enums
	 * @params: string
	 * @params: string
	 * @params: array
	 * @params: string
	 * @return: array
	 */
	private function _initParams($brand, $searchType, $searchCalc, $searchStDate, $searchEndDate, $searchStoreType, $searchAreaIds, $searchStoreName)
	{
		$params = new Fluent();
		
		#Op & Area有權限設定,故要再與查詢條件判別
		$allowOpCenterIds	= PosManager::getAllowOpCenters(); #只有取門店需要,無需代參數
		$allowAreaIds		= PosManager::getAllowAreas($searchAreaIds); #整併查詢參數
		
		#如果是芳珍, 暫不分權限
		if ($brand == Brand::FJVEGGIE)
		{
			$allowOpCenterIds 	= OpCenter::getAll();
			$allowAreaIds 		= Area::getAll();
		}
		
		if ($searchType == 'day') #有區間條件才要預設
			$searchEndDate 	= $searchStDate;
		
		$searchStDate	= Carbon::parse($searchStDate)->format('Y-m-d');
		$searchEndDate 	= Carbon::parse($searchEndDate)->format('Y-m-d');
		
		$hasHourlyData	= in_array('hourly', $searchCalc);
		$hasClosingData	= in_array('dailyClosing', $searchCalc);
		
		$functions 	= $this->parsingFunction($brand);
		$cacheKey 	= HelperLib::buildCacheKey([$functions->value, $allowOpCenterIds, $allowAreaIds, $searchType, $searchCalc, $searchStDate, $searchEndDate, $searchStoreType, $searchStoreName]);
		
		$params->brand($brand)->allowOpCenterIds($allowOpCenterIds)->allowAreaIds($allowAreaIds)
				->type($searchType)->calc($searchCalc)
				->hasHourlyData($hasHourlyData)->hasClosingData($hasClosingData)
				->stDate($searchStDate)->endDate($searchEndDate)
				->storeType($searchStoreType)->storeName($searchStoreName)
				->cacheKey($cacheKey);
		
		return $params;
	}
	
	/* Export data
	 * @params: enum
	 * @params: date
	 * @params: date
	 * @return: array
	 */
	public function export($token)
	{
		$cacheKey = hex2bin($token);
		
		if (! Cache::has($cacheKey))
			return ResponseLib::initialize()->fail('資料已過期，請重新查詢後下載');
		
		$currentUser = AppManager::getCurrentUser();
		Log::channel('appServiceLog')->info(Str::replaceArray('?', [$currentUser->getAvailableName(), $cacheKey], '[?]Export daily revenue data-?'));
		
		$sourceData = Cache::get($cacheKey);
		$type = $sourceData['type'];
		
		if ($type == 'day')
			$service = app(DayService::class);
		else if ($type == 'range')
			$service = app(RangeService::class);
		else if ($type == 'aov')
			$service = app(AovService::class);
		else
			return ResponseLib::initialize('檔案下載失敗，請重新查詢')->fail();
		
		return $service->export($sourceData);
	}
	
}
