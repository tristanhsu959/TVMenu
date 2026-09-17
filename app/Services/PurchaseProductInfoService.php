<?php

namespace App\Services;

use App\Services\PurchaseProductInfo\InfoService;
use App\Facades\AppManager;
use App\Facades\PurchaseManager;
use App\Repositories\PurchaseProductInfoRepository;
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
class PurchaseProductInfoService
{
	private $_statistics = [];
	
	public function __construct(protected PurchaseProductInfoRepository $_repository)
	{
		$this->_statistics = [
			'type'			=> '', 
			'brandId'		=> '', #export
			'brandCode'		=> '', 
			'hasOffShelf'	=> FALSE,
			'productIds'	=> [], #for search
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
			Brand::BAFANG	=> Functions::BF_PURCHASE_PRODUCT_INFO, 
			Brand::BUYGOOD	=> Functions::BG_PURCHASE_PRODUCT_INFO,
        };
	}
	
	/* 取出貨工廠清單
	 * @params: int
	 * @return: string
	 */
	public function getFactoryOptions($brand)
	{
		#不分OpCenter權限
		$allowOpCenter 	= PurchaseManager::getAllowOpCenters($brand);
		$list = PurchaseManager::getFactoryList($brand, $allowOpCenter);
		
		return $list;
	}
	
	/* ====================== 主流程 By Name ====================== */
	/* Search data
	 * @params: enum
	 * @params: date
	 * @params: date
	 * @params: string
	 * @return: array
	 */
	public function getStatistics($brand, $searchType, $searchProductTypes, $searchFactoryIds, $searchOffShelf, $searchShortCode, $searchProductName)
	{
		try
		{
			#產品沒有區域權限問題
			/* if (AppManager::hasAreaPermission() === FALSE)
				return ResponseLib::initialize($this->_statistics)->fail('此使用者無區域瀏覽權限'); */
			
			$params = $this->_initParams($brand, $searchType, $searchProductTypes, $searchFactoryIds, $searchOffShelf, $searchShortCode, $searchProductName);
			
			if (Cache::has($params->cacheKey))
			{
				Log::channel('appServiceLog')->info('Get purchase product info data from cache');
				
				$statistics = Cache::get($params->cacheKey); #cache data is response format
				return ResponseLib::initialize($statistics)->success();
			}
			else
			{
				Log::channel('appServiceLog')->info('Get purchase product info data from db');
				
				if ($params->type == 'info')
					$service = app(InfoService::class);
				else
					throw new Exception('查詢訂貨產品資訊時發生錯誤，無法識別查詢類型');
				
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
	private function _initParams($brand, $searchType, $searchProductTypes, $searchFactoryIds, $searchOffShelf, $searchShortCode, $searchProductName)
	{
		$params = new Fluent();
		
		#這裏是call appmanager不是current user
		$allowOpCenterIds	= PurchaseManager::getAllowOpCenters($brand);
		$allowAreaIds		= PurchaseManager::getAllowAreas(); #整併查詢參數
		
		$functions 	= $this->parsingFunction($brand);
		$cacheKey	= HelperLib::buildCacheKey([$functions->value, $allowOpCenterIds, $allowAreaIds, $searchType, $searchProductTypes, $searchFactoryIds, $searchOffShelf, $searchShortCode, $searchProductName]);
		
		#是否有查詢
		$hasPreorder = in_array('preorder', $searchProductTypes);
		$hasSupplier = in_array('supplier', $searchProductTypes);
		
		$params->brand($brand)->allowOpCenterIds($allowOpCenterIds)->allowAreaIds($allowAreaIds)
				->type($searchType)->hasPreorder($hasPreorder)->hasSupplier($hasSupplier)
				->factoryIds($searchFactoryIds)->hasOffShelf($searchOffShelf)
				->shortCode($searchShortCode)->productName($searchProductName)
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
		Log::channel('appServiceLog')->info(Str::replaceArray('?', [$currentUser->getAvailableName(), $cacheKey], '[?]Export purchase product info data-?'));
		
		$sourceData = Cache::get($cacheKey);
		$type = $sourceData['type'];
		
		if ($sourceData['type'] == 'info')
			$service = app(InfoService::class);
		else
			return ResponseLib::initialize()->fail('下載檔案發生錯誤，請重新查詢後下載');
		
		return $service->export($sourceData);
	}
}
