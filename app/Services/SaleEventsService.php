<?php

namespace App\Services;

use App\Facades\AppManager;
use App\Facades\PosManager;
use App\Repositories\SaleEventsRepository;
use App\Services\SaleEvents\MoonFestivalService;
use App\Libraries\ResponseLib;
use App\Libraries\HelperLib;
use App\Enums\Brand;
use App\Enums\Functions;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Fluent;
use Exception;
use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;


#Service
class SaleEventsService
{
	private $_statistics	= [];
	
	public function __construct(protected SaleEventsRepository $_repository)
	{
		#default
		$this->_statistics = [
			'brandId'		=> '', #export
			'type'			=> '',
			'startDate'		=> '', #Y-m-d
            'endDate'   	=> '',
			'store'			=> [],
			'exportToken'	=> '',
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
	 * @params: string
	 * @return: string
	 */
	public function parsingFunction($brand)
	{
		return match ($brand) 
		{
			Brand::BAFANG	=> Functions::BF_SALE_EVENTS, 
        };
	}
	
	/* ====================== 主流程 ====================== */
	/* Search data
	 * @params: enum
	 * @params: date
	 * @params: date
	 * @return: array
	 */
	public function getStatistics($brand, $searchType, $searchStDate, $searchEndDate, $searchAreaIds)
	{
		try
		{
			if (AppManager::hasAreaPermission() === FALSE)
				return ResponseLib::initialize($this->_statistics)->fail('此使用者無區域瀏覽權限');
			
			#Params都用pass(保留service可複用空間)
			$params = $this->_initParams($brand, $searchType, $searchStDate, $searchEndDate, $searchAreaIds);
			
			if (Cache::has($params->cacheKey))
			{
				Log::channel('appServiceLog')->info('Get sale events data from cache');
				
				$statistics = Cache::get($params->cacheKey); #cache data is response format
				
				return ResponseLib::initialize($statistics)->success();
			}
			else
			{
				Log::channel('appServiceLog')->info('Get sale events data from db');
				
				if ($params->type == 'moonFestival')
					$service = app(MoonFestivalService::class);
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
	
	/* Init input params (同條件,不同呈現initParams寫在主邏輯)
	 * @params: enums
	 * @params: string
	 * @params: string
	 * @params: integer
	 * @params: array
	 * @return: array
	 */
	private function _initParams($brand, $searchType, $searchStDate, $searchEndDate, $searchAreaIds)
	{
		$params = new Fluent();
		
		#Op & Area有權限設定,故要再與查詢條件判別
		#只有取門店需要
		$allowOpCenterIds	= PosManager::getAllowOpCenters();
		$allowAreaIds		= PosManager::getAllowAreas($searchAreaIds); #整併查詢參數
		
		$searchEndDate 	= empty($searchEndDate) ? now()->format('Y-m-d') : $searchEndDate;
		$functions 		= $this->parsingFunction($brand);
		$cacheKey 		= HelperLib::buildCacheKey([$functions->value, $allowOpCenterIds, $allowAreaIds, $searchType, $searchStDate, $searchEndDate]);
		
		$params->brand($brand)->allowOpCenterIds($allowOpCenterIds)->allowAreaIds($allowAreaIds)
				->type($searchType)->stDate($searchStDate)->endDate($searchEndDate)
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
		Log::channel('appServiceLog')->info(Str::replaceArray('?', [$currentUser->getAvailableName(), $cacheKey], '[?]Export moon festival data-?'));
		
		$sourceData = Cache::get($cacheKey);
		
		$service = app(MoonFestivalService::class);
		
		return $service->export($sourceData);
	}
	
}
