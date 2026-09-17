<?php

namespace App\Services;

use App\Facades\AppManager;
use App\Repositories\PurchaseSalesRepository;
use App\Libraries\ResponseLib;
use App\Libraries\HelperLib;
use App\Enums\Brand;
use App\Enums\Functions;
use App\Enums\Area;
use App\Libraries\Purchase\AreaLib;
use App\Services\PurchaseSales\StoreService;
use App\Services\PurchaseSales\OrderService;
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

class PurchaseSalesService
{
	private $_statistics = [];
	
	public function __construct(protected PurchaseSalesRepository $_repository)
	{
		$this->_statistics = [
			'brandId'			=> '', 
			'searchType'		=> '', #可移除
			'searchDate'		=> '', #Y-m-d
			'searchAreaId' 		=> 0,
			'searchStoreName' 	=> '',
            'storeList' 		=> [],
			'purchaseData' 		=> [], 
			'salesData'			=> [],
			'exportToken'		=> '', #export
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
			Brand::BAFANG	=> Functions::BF_PURCHASE_SALES, 
			Brand::BUYGOOD	=> Functions::BG_PURCHASE_SALES,
        };
	}
	
	/*************** Ger active store list ***************/
	/* Get store list
	 * @params: enums
	 * @params: string
	 * @params: string
	 * @return: array
	 */
	public function getStoreList($brand, $searchType, $searchDate, $searchAreaId, $searchStoreName)
	{
		try
		{
			if (AppManager::hasAreaPermission() === FALSE)
				return ResponseLib::initialize($this->_statistics)->fail('此使用者無區域瀏覽權限');
			
			$service = app(StoreService::class);
			
			$this->_statistics = $service->getActiveList($brand, $searchType, $searchDate, $searchAreaId, $searchStoreName);
			
			#Save search to session, 在主service處理
			$functions = ($this->parsingFunction($brand))->value;
			$service->saveToSession($functions);
			
			return ResponseLib::initialize($this->_statistics)->success();
		}
		catch(Exception $e)
		{
			return ResponseLib::initialize($this->_statistics)->fail($e->getMessage());
		}
	}
	
	/* 取Last store list
	 * @params: int
	 * @return: string
	 */
	public function getLastSearchData($function)
	{
		$service = app(StoreService::class);
		
		$data = $service->getFromSession($function->value);
		
		if ($data === FALSE)
			return ResponseLib::initialize($this->_statistics)->fail();
		
		#data is statistics format
		return ResponseLib::initialize($data)->success();
	}
	
	/*************** Get detail purchase & sales data ***************/
	/* Get statistics from order & pos
	 * @params: enum
	 * @params: date
	 * @params: int
	 * @return: array
	 */
	public function getStatistics($brand, $searchDate, $searchStoreId)
	{
		try
		{
			if (AppManager::hasAreaPermission() === FALSE)
				return ResponseLib::initialize($this->_statistics)->fail('此使用者無區域瀏覽權限');
			
			$service = app(OrderService::class);
            $functions = ($this->parsingFunction($brand))->value; #for cache
                
            $this->_statistics = $service->analysis($brand, $functions, $searchDate, $searchStoreId);				
			
			return ResponseLib::initialize($this->_statistics)->success();
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
	public function export($token)
	{
		$cacheKey = hex2bin($token);
		
		if (! Cache::has($cacheKey))
			return ResponseLib::initialize()->fail('資料已過期，請重新查詢後下載');
		
		$currentUser = AppManager::getCurrentUser();
		Log::channel('appServiceLog')->info(Str::replaceArray('?', [$currentUser->getAvailableName(), $cacheKey], '[?]Export purchase & sales data-?'));
		
		$sourceData = Cache::get($cacheKey);
		$service = app(OrderService::class); #只有detail有download
		
		return $service->export($sourceData);
	}
}
