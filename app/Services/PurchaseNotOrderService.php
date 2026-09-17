<?php

namespace App\Services;

use App\Services\PurchaseNotOrder\ProductService;
use App\Facades\AppManager;
use App\Facades\PurchaseManager;
use App\Repositories\PurchaseNotOrderRepository;
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
class PurchaseNotOrderService
{
	private $_statistics = [];
	
	public function __construct(protected PurchaseNotOrderRepository $_repository)
	{
		$this->_statistics = [
			'type'		=> '', #store,factory
			'calc'		=> '', #日,月
			'by'		=> '', #keyword,category
			'brandId'		=> '', #export
			'brandCode'		=> '', 
			'startDate'		=> '', #Y-m-d
            'endDate'   	=> '',
			'productIds'	=> [],
			'dateList'		=> [],
			'productList'	=> [],
			'storeList'		=> [],
			'factoryList'	=> [],
			'data'			=> [],
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
			Brand::BAFANG	=> Functions::BF_PURCHASE_NOT_ORDER, 
			Brand::BUYGOOD	=> Functions::BG_PURCHASE_NOT_ORDER,
        };
	}
	
	/* 取出貨產品設定, 有啟用的產品清單 - purchase product setting(後台設定)
	 * @params: int
	 * @return: string
	 */
	public function getProductOptions($brand)
	{
		$allowOpCenter 	= PurchaseManager::getAllowOpCenters($brand);
		$productOptions = PurchaseManager::getEnableProductSettingsAndCategory($brand, $allowOpCenter);
		
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
	public function getStatistics($brand, $searchType, $searchCalc, $searchStDate, /* $searchEndDate,  */$searchAreaIds, $searchBy, $searchKeyword, $searchCategory, $searchShortCodes)
	{
		try
		{
			if (AppManager::hasAreaPermission() === FALSE)
				return ResponseLib::initialize($this->_statistics)->fail('此使用者無區域瀏覽權限');
			
			$params = $this->_initParams($brand, $searchType, $searchCalc, $searchStDate, /* $searchEndDate,  */$searchAreaIds, $searchBy, $searchKeyword, $searchCategory, $searchShortCodes);
			
			if (Cache::has($params->cacheKey))
			{
				Log::channel('appServiceLog')->info('Get shipments data from cache');
				
				$statistics = Cache::get($params->cacheKey); #cache data is response format
				return ResponseLib::initialize($statistics)->success();
			}
			else
			{
				Log::channel('appServiceLog')->info('Get shipments data from db');
				
				#先取Product Id
				$this->_getProductId($params);
				
				if ($params->type == 'filling' OR $params->type == 'product')
					$service = app(ProductService::class);
				else
					throw new Exception('查詢訂貨總量時發生錯誤');
				
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
	private function _initParams($brand, $searchType, $searchCalc, $searchStDate, /* $searchEndDate,  */$searchAreaIds, $searchBy, $searchKeyword, $searchCategory, $searchShortCodes)
	{
		$params = new Fluent();
		
		#這裏是call appmanager不是current user
		$allowOpCenterIds	= PurchaseManager::getAllowOpCenters($brand); #只有取門店需要,無需代參數
		$allowAreaIds		= PurchaseManager::getAllowAreas($searchAreaIds); #整併查詢參數
		
		$searchEndDate 	= $searchStDate;
		$functions 		= $this->parsingFunction($brand);
		
		$cacheBy = ($searchType == 'filling') ? '' : $searchBy;
		#空白Lib會過濾
		$cacheKey = HelperLib::buildCacheKey([$functions->value, $allowOpCenterIds, $allowAreaIds, $searchType, $searchCalc, $searchStDate, $cacheBy, $searchKeyword, $searchCategory, $searchShortCodes]);
		
		$params->brand($brand)->allowOpCenterIds($allowOpCenterIds)->allowAreaIds($allowAreaIds)
				->stDate($searchStDate)->endDate($searchEndDate)
				->type($searchType)->calc($searchCalc)->by($searchBy)
				->keyword($searchKeyword)->category($searchCategory)->shortCodes($searchShortCodes)
				->cacheKey($cacheKey);
		
		return $params;
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
			$brand 		= $params->brand;
			$opCenterIds= $params->allowOpCenterIds;
			
			if ($params->type == 'filling')
			{
				$brandId = $brand->value;
				$params->shortCodes = config("web.purchase.product_type.notOrderFillingProducts.{$brandId}");
				$productIds = PurchaseManager::getProductIdByShortCode($brand, $opCenterIds, $params->shortCodes);
			}
			else if ($params->type == 'product')
			{
				if ($params->by == 'keyword')
					$productIds = PurchaseManager::getProductIdByName($brand, $opCenterIds, $params->keyword);
				else
					$productIds = PurchaseManager::getProductIdByShortCode($brand, $opCenterIds, $params->shortCodes);
			}
			else
				$productIds = [];
			
			if (empty($productIds))
				throw new Exception('查無此產品');
			
			$params->productIds = $productIds;
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception($e->getMessage());
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
		Log::channel('appServiceLog')->info(Str::replaceArray('?', [$currentUser->getAvailableName(), $cacheKey], '[?]Export purchase not order data-?'));
		
		$sourceData = Cache::get($cacheKey);
		$service = app(ProductService::class);
		
		return $service->export($sourceData);
	}
}
