<?php

namespace App\Services;

use App\Services\PurchaseReport\PerformanceService;
use App\Services\PurchaseReport\EmployeePrService;
use App\Services\PurchaseReport\ExtraOrderService;
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

#訂貨相關報表
class PurchaseReportService
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
			Brand::BAFANG	=> Functions::BF_PURCHASE_REPORT, 
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
	public function getStatistics($brand, $searchType, $searchBrand, $searchStDate, $searchEndDate, $searchOpCenterIds, $searchAreaIds, $searchProductCodes)
	{
		try
		{
			if (AppManager::hasAreaPermission() === FALSE)
				return ResponseLib::initialize($this->_statistics)->fail('此使用者無區域瀏覽權限');
			
			$params = $this->_initParams($brand, $searchType, $searchBrand, $searchStDate, $searchEndDate, $searchOpCenterIds, $searchAreaIds, $searchProductCodes);
			
			if (Cache::has($params->cacheKey))
			{
				Log::channel('appServiceLog')->info('Get purchase report data from cache');
				
				$statistics = Cache::get($params->cacheKey); #cache data is response format
				
				return ResponseLib::initialize($statistics)->success();
			}
			else
			{
				Log::channel('appServiceLog')->info('Get purchase report data from db');
				
				if ($params->type == 'performance')
					$service = app(PerformanceService::class);
				else if ($params->type == 'employeePr')
					$service = app(EmployeePrService::class);
				else if ($params->type == 'extraOrder')
					$service = app(ExtraOrderService::class);
				else
					return ResponseLib::initialize($this->_statistics)->fail('執行訂貨統計時發生錯誤，無法識別報表類型');
				
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
	private function _initParams($brand, $searchType, $searchBrand, $searchStDate, $searchEndDate, $searchOpCenterIds, $searchAreaIds, $searchProductCodes)
	{
		$params = new Fluent();
		
		#報表目前只有八方有, 暫不考慮御廚的情境
		$allowOpCenterIds	= PurchaseManager::getAllowOpCenters($brand, $searchOpCenterIds); #只有取門店需要,無需代參數
		$allowAreaIds		= PurchaseManager::getAllowAreas($searchAreaIds); #整併查詢參數
		
		$functions 	= $this->parsingFunction($brand);
		$cacheKey 	= HelperLib::buildCacheKey([$functions->value, $allowOpCenterIds, $allowAreaIds, $searchType, $searchBrand, $searchStDate, $searchEndDate, $searchProductCodes]);
		
		#只有營運狀況才有此選項
		$searchBrand = ($searchType == 'performance') ? $searchBrand : NULL;
		
		$params->brand($brand)->allowOpCenterIds($allowOpCenterIds)->allowAreaIds($allowAreaIds)
				->type($searchType)->whereBrandId($searchBrand)
				->stDate($searchStDate)->endDate($searchEndDate)
				->productCodes($searchProductCodes)
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
		Log::channel('appServiceLog')->info(Str::replaceArray('?', [$currentUser->getAvailableName(), $cacheKey], '[?]Export 營業概況 data-?'));
		
		$sourceData = Cache::get($cacheKey);
		$type = $sourceData['type'];
		
		if ($type == 'performance')
			$service = app(PerformanceService::class);
		else if ($type == 'employeePr')
			$service = app(EmployeePrService::class);
		else if ($type == 'extraOrder')
			$service = app(ExtraOrderService::class);
		else
			return ResponseLib::initialize('檔案下載發生錯誤，請重新查詢')->fail();
		
		return $service->export($sourceData);
	}
}
