<?php

namespace App\Services;

use App\Facades\AppManager;
use App\Facades\PosManager;
use App\Repositories\SalesRepository;
use App\Services\Sales\ProductService;
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


#Service BF | BG 共用
class SalesService
{
	private $_statistics	= [];
	
	public function __construct(protected SalesRepository $_repository)
	{
		#default
		$this->_statistics = [
			'brandId'		=> '', #export
			'type'			=> '',
			'startDate'		=> '', #Y-m-d
            'endDate'   	=> '',
			'storeName'		=> '',
			'store'			=> [],
			'area' 			=> [],
			'productList'	=> [],
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
			Brand::BAFANG	=> Functions::BF_SALES, 
			Brand::BUYGOOD	=> Functions::BG_SALES,
        };
	}
	
	/* 取銷售產品設定, 有啟用的產品清單(Options) - sales product setting
	 * @params: int
	 * @return: string
	 */
	public function getEnableProducts($brandId)
	{
		/*0 => array:3 [
			"productId" => 1
			"productName" => "招牌鍋貼"
			"categoryId" => 1
		]*/
		$enableProducts = $this->_repository->getEnableProducts($brandId);
		
		#Build category & product mapping
		#Category list
		$category = collect($enableProducts)->pluck('categoryId')->unique()->mapWithKeys(function($item, $key) use($brandId){
			$name = config("web.sales.category.{$brandId}.$item");
			return [$item => $name];
		})->toArray();
		
		#Product list
		$products = collect($enableProducts)->groupBy('categoryId')->map(function($items, $key){
			return $items->map(function($item, $key){
				$temp['id']		= $item['productId'];
				$temp['name'] 	= $item['productName'];
				return $temp;
			});
			
			return $items;
		})->toArray();
		
		return [$category, $products];
	}
	
	/* ====================== 主流程 ====================== */
	/* Search data
	 * @params: enum
	 * @params: date
	 * @params: date
	 * @return: array
	 */
	public function getStatistics($brand, $searchType, $searchStDate, $searchEndDate, $searchAreaIds, $searchStoreName, $searchCategory, $searchProductIds)
	{
		try
		{
			if (AppManager::hasAreaPermission() === FALSE)
				return ResponseLib::initialize($this->_statistics)->fail('此使用者無區域瀏覽權限');
			
			#Params都用pass(保留service可複用空間)
			$params = $this->_initParams($brand, $searchType, $searchStDate, $searchEndDate, $searchAreaIds, $searchStoreName, $searchCategory, $searchProductIds);
			
			if (Cache::has($params->cacheKey))
			{
				Log::channel('appServiceLog')->info('Get sales data from cache');
				
				$statistics = Cache::get($params->cacheKey); #cache data is response format
				
				return ResponseLib::initialize($statistics)->success();
			}
			else
			{
				Log::channel('appServiceLog')->info('Get sales data from db');
				
				if ($params->type == 'total')
					$service = app(ProductService::class);
				/* else if ($params->type == 'store')
					$service = app(StoreService::class);
				 */
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
	private function _initParams($brand, $searchType, $searchStDate, $searchEndDate, $searchAreaIds, $searchStoreName, $searchCategory, $searchProductIds)
	{
		$params = new Fluent();
		
		#Op & Area有權限設定,故要再與查詢條件判別
		#只有取門店需要
		$allowOpCenterIds	= PosManager::getAllowOpCenters();
		$allowAreaIds		= PosManager::getAllowAreas($searchAreaIds); #整併查詢參數
		
		$searchEndDate 	= empty($searchEndDate) ? now()->format('Y-m-d') : $searchEndDate;
		$functions 		= $this->parsingFunction($brand);
		$cacheKey 		= HelperLib::buildCacheKey([$functions->value, $allowOpCenterIds, $allowAreaIds, $searchType, $searchStDate, $searchEndDate, $searchStoreName, $searchCategory, $searchProductIds]);
		
		$params->brand($brand)->allowOpCenterIds($allowOpCenterIds)->allowAreaIds($allowAreaIds)
				->type($searchType)->stDate($searchStDate)->endDate($searchEndDate)
				->storeName($searchStoreName)
				->category($searchCategory)->productIds($searchProductIds)
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
		Log::channel('appServiceLog')->info(Str::replaceArray('?', [$currentUser->getAvailableName(), $cacheKey], '[?]Export shipment data-?'));
		
		$sourceData = Cache::get($cacheKey);
		
		$service = app(ProductService::class);
		
		return $service->export($sourceData);
	}
	
}
