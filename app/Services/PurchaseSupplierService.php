<?php

namespace App\Services;

use App\Services\PurchaseSupplier\OrderService;
use App\Facades\AppManager;
use App\Facades\PurchaseManager;
use App\Repositories\PurchaseSupplierRepository;
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

#當主Service
class PurchaseSupplierService
{
	private $_statistics = [];
	
	public function __construct(protected PurchaseSupplierRepository $_repository)
	{
		$this->_statistics = [
			'type'			=> '', 
			'where'			=> '', #keyword,category
			'brandId'		=> '', #export
			'brandCode'		=> '', 
			'startDate'		=> '', #Y-m-d
            'endDate'   	=> '',
			'productIds'	=> [],
			'productList'	=> [],
			'storeList'		=> [],
			'store'			=> [],
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
			Brand::BAFANG	=> Functions::BF_PURCHASE_SUPPLIER, 
			Brand::BUYGOOD	=> Functions::BG_PURCHASE_SUPPLIER,
        };
	}
	
	/* 取出貨產品設定, 有啟用的產品清單 - purchase product setting(後台設定)
	 * @params: int
	 * @return: string
	 */
	public function getProductOptions($brand)
	{
		$allowOpCenter 	= PurchaseManager::getAllowOpCenters($brand);
		$productOptions = PurchaseManager::getSupplierProductWithCategory();
		
		#若有個別處理寫在這
		return $productOptions;
	}
	
	/* ====================== 主流程 By Name ====================== */
	/* Search data
	 * @params: enum
	 * @params: date
	 * @params: date
	 * @params: string
	 * @return: array
	 */
	public function getStatistics($brand, $searchType, $searchStDate, $searchEndDate, $searchAreaIds, 
									$searchWhere, $searchKeyword, $searchCategory, $searchProductIds)
	{
		try
		{
			if (AppManager::hasAreaPermission() === FALSE)
				return ResponseLib::initialize($this->_statistics)->fail('此使用者無區域瀏覽權限');
			
			$params = $this->_initParams($brand, $searchType, $searchStDate, $searchEndDate, $searchAreaIds, 
											$searchWhere, $searchKeyword, $searchCategory, $searchProductIds);
			
			if (Cache::has($params->cacheKey))
			{
				Log::channel('appServiceLog')->info('Get supplier order data from cache');
				
				$statistics = Cache::get($params->cacheKey); #cache data is response format
				return ResponseLib::initialize($statistics)->success();
			}
			else
			{
				Log::channel('appServiceLog')->info('Get supplier order data from db');
				
				if ($params->type == 'total')
					$service = app(OrderService::class);
				else
					throw new Exception('查詢供應商出貨總量時發生錯誤，無法識別查詢類型');
				
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
	private function _initParams($brand, $searchType, $searchStDate, $searchEndDate, $searchAreaIds, 
							$searchWhere, $searchKeyword, $searchCategory, $searchProductIds)
	{
		$params = new Fluent();
		
		#這裏是call appmanager不是current user
		$allowOpCenterIds	= PurchaseManager::getAllowOpCenters($brand); #只有取門店需要,無需代參數
		$allowAreaIds		= PurchaseManager::getAllowAreas($searchAreaIds); #整併查詢參數
		
		$searchEndDate 	= empty($searchEndDate) ? now()->format('Y-m-d') : $searchEndDate;
		$functions 		= $this->parsingFunction($brand);
		
		$cacheKeyItems = [$functions->value, $allowOpCenterIds, $allowAreaIds, $searchType, $searchStDate, $searchEndDate];
		
		if ($searchType == 'total' && $searchWhere == 'keyword')
			$cacheKeyItems[] = [$searchWhere, $searchKeyword];
		else if ($searchType == 'total' && $searchWhere == 'category')
			$cacheKeyItems[] = [$searchWhere, $searchCategory, $searchProductIds];
		
		$cacheKey = HelperLib::buildCacheKey($cacheKeyItems);
		
		$params->brand($brand)->allowOpCenterIds($allowOpCenterIds)->allowAreaIds($allowAreaIds)
				->stDate($searchStDate)->endDate($searchEndDate)
				->type($searchType)->where($searchWhere)
				->keyword($searchKeyword)->category($searchCategory)->productIds($searchProductIds)
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
		Log::channel('appServiceLog')->info(Str::replaceArray('?', [$currentUser->getAvailableName(), $cacheKey], '[?]Export purchase supplier order data-?'));
		
		$sourceData = Cache::get($cacheKey);
		$type 	= $sourceData['type'];
		
		if ($type == 'total')
			$service = app(OrderService::class);
		else
			return ResponseLib::initialize()->fail('下載檔案發生錯誤，請重新查詢後下載');
		
		return $service->export($sourceData);
	}
}
