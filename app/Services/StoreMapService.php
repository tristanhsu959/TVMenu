<?php

namespace App\Services;

use App\Facades\AppManager;
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
use Carbon\CarbonPeriod;
use Exception;
use OpenSpout\Writer\Common\Creator\WriterEntityFactory;
use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;

class StoreMapService
{
	private $_statistics = [];
   
	public function __construct(protected MerchantRepository $_repository)
	{
		$this->_statistics = [
			'brandId'		=> '', #export
			'modeType'			=> '',
			'startDate'		=> '', #Y-m-d
			'endDate'		=> '', #Y-m-d
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
	public function getStatistics($brand, $searchType, $searchStDate)
	{
		try
		{
			if (AppManager::hasAreaPermission() === FALSE)
				return ResponseLib::initialize($this->_statistics)->fail('此使用者無區域瀏覽權限');
			
			$currentUser = AppManager::getCurrentUser();
			$userAreaIds = $currentUser->roleArea; #
			
			#Check cache
			$functions = $this->parsingFunction($brand);
			$searchEndDate = $searchStDate; #目前只有單日查詢
			#$cacheKey = implode(':', [$functions->value, $searchType, $searchStDate]);
			$cacheKey = HelperLib::buildCacheKey([$functions->value, $userAreaIds, $searchType, $searchStDate]);
			
			$this->_statistics['modeType']	= $searchType;
			$this->_statistics['brandId']	= $brand->value; 
			$this->_statistics['startDate'] = ($searchType == 'info') ? '' : (new Carbon($searchStDate))->format('Y-m-d'); 
			$this->_statistics['endDate'] 	= $this->_statistics['startDate'];
			
			if (Cache::has($cacheKey))
			{
				Log::channel('appServiceLog')->info('Get mechant data from cache');
				
				$statistics = Cache::get($cacheKey); #cache data is response format
				return ResponseLib::initialize($statistics)->success();
			}
			else
			{
				Log::channel('appServiceLog')->info('Get mechant data from db');
				
				if ($searchType == 'info')
					$service = app(InfoService::class);
				else
					$service = app(DayoffService::class);
				
				#執行統計
				$this->_statistics = $service->analysis($this->_statistics);
				
				#無值不cache
				if (! empty(Arr::flatten($this->_statistics['info'])) OR ! empty(Arr::flatten($this->_statistics['dayoff'])))
				{
					$this->_statistics['exportToken'] 	= bin2hex($cacheKey); #hex2bin
					$this->_statistics['exportName']	= ($searchType == 'info') ? '門店資訊' : '店休資訊';
					Cache::put($cacheKey, $this->_statistics, now()->addMinutes(10));
				}
				
				return ResponseLib::initialize($this->_statistics)->success();
			}
		}
		catch(Exception $e)
		{
			return ResponseLib::initialize($this->_statistics)->fail($e->getMessage());
		}
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
		$modeType = $sourceData['modeType'];
		
		if ($modeType == 'info')
			$service = app(InfoService::class);
		else
			$service = app(DayoffService::class);
		
		return $service->export($sourceData);
	}
}
