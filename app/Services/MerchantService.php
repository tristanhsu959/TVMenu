<?php

namespace App\Services;

use App\Facades\AppManager;
use App\Facades\PurchaseManager;
use App\Services\Merchant\InfoService;
use App\Services\Merchant\DayoffService;
use App\Repositories\MerchantRepository;
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

class MerchantService
{
	private $_statistics = [];
   
	public function __construct(protected MerchantRepository $_repository)
	{
		$this->_statistics = [
			'brandId'		=> '', #export
			'type'			=> '',
			'startDate'		=> '', #Y-m-d
			'endDate'		=> '', #Y-m-d
			'opCenterIds'	=> [],
			'areaIds'		=> [],
            'info' 			=> [],
			'dayoff' 		=> [],
			'areaDayoff'	=> [],
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
			Brand::BAFANG	=> Functions::BF_MERCHANT, 
			Brand::BUYGOOD	=> Functions::BG_MERCHANT,
			Brand::FJVEGGIE	=> Functions::FJ_MERCHANT,
        };
	}
	
	/* ====================== 主流程 ====================== */
	/* Search data
	 * @params: enum
	 * @params: string
	 * @params: date
	 * @return: array
	 */
	public function getStatistics($brand, $searchType, $searchStDate, $searchAreaIds)
	{
		try
		{
			if (AppManager::hasAreaPermission() === FALSE)
				return ResponseLib::initialize($this->_statistics)->fail('此使用者無區域瀏覽權限');
			
			$params = $this->_initParams($brand, $searchType, $searchStDate, $searchAreaIds);
			
			if (Cache::has($params->cacheKey))
			{
				Log::channel('appServiceLog')->info('Get mechant data from cache');
				
				$statistics = Cache::get($params->cacheKey); #cache data is response format
				return ResponseLib::initialize($statistics)->success();
			}
			else
			{
				Log::channel('appServiceLog')->info('Get mechant data from db');
				
				if ($searchType == 'info')
					$service = app(InfoService::class);
				else if ($searchType == 'dayOff')
					$service = app(DayoffService::class);
				else
					throw new Exception('無法識別查詢條件');
				
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
	private function _initParams($brand, $searchType, $searchStDate, $searchAreaIds)
	{
		$params = new Fluent();
		
		#此功能無法被歸類銷售或訂貨,故取全區
		$allowOpCenterIds	= PurchaseManager::getAllOpCenters();
		$allowAreaIds		= PurchaseManager::getAllAreas($searchAreaIds);
		
		$functions		= $this->parsingFunction($brand);
		$searchStDate 	= empty($searchStDate) ? '' : Carbon::parse($searchStDate)->format('Y-m-d'); 
		$searchEndDate 	= empty($searchStDate) ? '' : Carbon::parse($searchStDate)->addDay()->format('Y-m-d');
		
		$cacheKey = HelperLib::buildCacheKey([$functions->value, $allowOpCenterIds, $allowAreaIds, $searchType, $searchStDate]);
		
		$params->brand($brand)->allowOpCenterIds($allowOpCenterIds)->allowAreaIds($allowAreaIds)
				->type($searchType)->stDate($searchStDate)->endDate($searchStDate)
				->cacheKey($cacheKey);
		
		return $params;
	}
	
	/* Export data
	 * @params: enum
	 * @params: date
	 * @params: date
	 * @return: array
	 */
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
		Log::channel('appServiceLog')->info(Str::replaceArray('?', [$currentUser->getAvailableName(), $cacheKey], '[?]Export merchant data-?'));
		
		$sourceData = Cache::get($cacheKey);
		$type = $sourceData['type'];
		
		if ($type == 'info')
			$service = app(InfoService::class);
		else
			$service = app(DayoffService::class);
		
		return $service->export($sourceData);
	}
}
