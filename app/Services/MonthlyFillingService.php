<?php

namespace App\Services;

use App\Services\MonthlyFilling\FactoryService;
use App\Services\MonthlyFilling\StoreService;
use App\Facades\AppManager;
use App\Facades\PurchaseManager;
use App\Libraries\ResponseLib;
use App\Libraries\HelperLib;
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

#月初報表(餡料)
class MonthlyFillingService
{
	private $_statistics = [];
	
	public function __construct()
	{
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
	
	/* Parsing function by brand(BF only)
	 * @params: enums
	 * @return: string
	 */
	public function parsingFunction($brand)
	{
		return match ($brand) 
		{
			Brand::BAFANG	=> Functions::BF_MONTHLY_FILLING, 
        };
	}
	
	/* ====================== 主流程 By Name ====================== */
	/* Search data
	 * @params: enum
	 * @params: enum
	 * @params: date
	 * @params: date
	 * @params: string
	 * @params: string
	 * @return: array
	 */
	public function getStatistics($brand, $searchStDate, $searchEndDate, $searchType, $searchRange)
	{
		try
		{
			if (AppManager::hasAreaPermission() === FALSE)
				return ResponseLib::initialize($this->_statistics)->fail('此使用者無區域瀏覽權限');
			
			$params = $this->_initParams($brand, $searchStDate, $searchEndDate, $searchType, $searchRange);
			
			if (Cache::has($params->cacheKey))
			{
				Log::channel('appServiceLog')->info('Get monthly filling data from cache');
				
				$statistics = Cache::get($params->cacheKey); #cache data is response format
				
				return ResponseLib::initialize($statistics)->success();
			}
			else
			{
				Log::channel('appServiceLog')->info('Get monthly filling data from db');
				
				if ($params->type == 'store')
					$service = app(StoreService::class);
				else
					$service = app(FactoryService::class);
				
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
	
	/* Init input params
	 * @params: enums
	 * @params: string
	 * @params: string
	 * @params: array
	 * @params: string
	 * @return: array
	 */
	private function _initParams($brand, $searchStDate, $searchEndDate, $searchType, $searchRange)
	{
		$params = new Fluent();
		
		#此功能暫不提供營運中心, 區域選項
		$allowOpCenterIds	= PurchaseManager::getAllowOpCenters($brand); #只有取門店需要,無需代參數
		$allowAreaIds		= PurchaseManager::getAllowAreas(); #整併查詢參數
		
		#轉換日期
		if ($searchRange == 'year')
		{
			$year = Carbon::now()->year;
			$searchStDate 	= Carbon::create($year)->startOfYear()->toDateString();
			$searchEndDate 	= Carbon::create($year)->endOfYear()->toDateString();
		}
		else if ($searchRange == 'month')
		{
			$searchStDate = Carbon::createFromFormat('!Y-m', $searchStDate)->toDateString();
			$searchEndDate = Carbon::createFromFormat('!Y-m', $searchEndDate)->endOfMonth()->toDateString();
		}
			
		$functions 	= $this->parsingFunction($brand);
		$cacheKey 	= HelperLib::buildCacheKey([$functions->value, $allowOpCenterIds, $allowAreaIds, $searchStDate, $searchEndDate, $searchType, $searchRange]);
		
		$params->brand($brand)->allowOpCenterIds($allowOpCenterIds)->allowAreaIds($allowAreaIds)
				->stDate($searchStDate)->endDate($searchEndDate)
				->type($searchType)->range($searchRange)
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
		Log::channel('appServiceLog')->info(Str::replaceArray('?', [$currentUser->getAvailableName(), $cacheKey], '[?]Export monthly filling data-?'));
		
		$sourceData = Cache::get($cacheKey);
		$type = $sourceData['type'];
		
		if ($type == 'store')
			$service = app(StoreService::class);
		else
			$service = app(FactoryService::class);
		
		return $service->export($sourceData);
	}
}
