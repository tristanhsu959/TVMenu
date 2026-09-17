<?php

namespace App\Services\PurchaseSales;

use App\Facades\AppManager;
use App\Facades\PurchaseManager;
use App\Repositories\PurchaseSalesRepository;
use App\Libraries\ResponseLib;
use App\Libraries\Purchase\AreaLib;
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

#partial Service
class StoreService
{
	private $_statistics	= [];
   
	public function __construct(protected PurchaseSalesRepository $_repository)
	{
		$this->_statistics = [
			'brandId'			=> '', 
			'searchType'		=> '', 
			'searchDate'		=> '', #Y-m-d
			'searchAreaId' 		=> 0,
			'searchStoreName' 	=> '',
            'store' 			=> [],
		];
	}
	
	/* ====================== 主流程 By Name ====================== */
	/* 只取有效店家
	 * @params: enums
	 * @params: string
	 * @params: int
	 * @params: string
	 * @return: array
	 */
	public function getActiveList($brand, $searchType, $searchDate, $searchAreaId, $searchStoreName)
	{
		try
		{
			#因不同邏輯,故init params放在child service
			$params = $this->_initParams($brand, $searchType, $searchDate, $searchAreaId, $searchStoreName);
			
			$this->_getListFromDB($params);
			
			$this->_buildOutput($params);
			
			$this->_generateStatistics($params);
			
			return $this->_statistics;
		}
		catch(Exception $e)
		{
			throw new Exception($e->getMessage());
		}
	}
	
	/* Init input params
	 * @params: enums
	 * @params: string
	 * @params: int
	 * @params: string
	 * @return: array
	 */
	private function _initParams($brand, $searchType, $searchDate, $searchAreaId, $searchStoreName)
	{
		#參數各自獨立, 故寫在partial service
		$params = new Fluent();
		
		$currentUser	= AppManager::getCurrentUser();
		$userAreaIds	= $currentUser->roleArea;
		$area 			= Area::tryFrom($searchAreaId);
		
		#返回時會用到, 故參數都存
		$params->brand($brand)->userAreaIds($userAreaIds)
				->searchType($searchType)->searchDate($searchDate)
				->searchAreaId($searchAreaId)->searchAreaName($area->label())
				->searchStoreName($searchStoreName);
		
		return $params;
	}
	
	/* Generate statistics data
	 * @params: object
	 * @return: array
	 */
	private function _generateStatistics($params)
	{
		$this->_statistics['brandId']			= $params->brand->value;
		$this->_statistics['brandCode']			= $params->brand->code();
		$this->_statistics['searchType']		= $params->searchType;
		$this->_statistics['searchDate']		= $params->searchDate;
		$this->_statistics['searchAreaId']		= $params->searchAreaId;
		$this->_statistics['searchAreaName']	= $params->searchAreaName;
		$this->_statistics['searchStoreName']	= $params->searchStoreName;
		$this->_statistics['store']				= $params->store;
		$this->_statistics['exportToken']		= NULL; #保留供判斷用
	}
	
	/* 取Active store list
	 * @params: int
	 * @return: string
	 */
	private function _getListFromDB($params)
	{
		try
		{
			#以訂貨系統的門店為基準(不含蘿蔔,因不見得有POS對應, 取資料時才處理蘿蔔店)
			#固定格式才call purchase manager的store list, 這裏暫自行處理
			$areaIds = empty($params->searchAreaId) ? $params->userAreaIds : $params->searchAreaId;
			$result = $this->_repository->getActiveStoreListFromPurchase($params->brand, $areaIds, $params->searchStoreName);
			
			$result = collect($result)->map(function($item, $key){
				$area = AreaLib::toArea(intval($item['areaId']));
				
				$item['areaId']		= $area->value;
				$item['areaName']	= $area->label();
				$item['openDate']	= Carbon::parse($item['openDate'])->format('Y-m-d');
				$item['storeKey']	= PurchaseManager::buildStoreKey($item['storeNo']);
				
				return $item;
			})->toArray();
			
			$params->set('store.data', $result);
			
			return $params;
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('讀取門店資料發生錯誤');
		}
	}
	
	/* Format output
	 * @params: 
	 * @return: 
	 */
	private function _buildOutput($params)
	{
		$params->set('store.header', ['PosId', '區域', '門店代號', '門店名稱', '地址', '加盟主', '開店日期', '查看']);
			
		$data = collect($params->store['data'])->sortBy('areaId')->values()->all();
		$params->set('store.data', $data);
	}
	
	public function saveToSession($function)
	{
		session()->put("Sess::List:{$function}", $this->_statistics);
	}
	
	
	public function getFromSession($function)
	{
		if (session()->missing("Sess::List:{$function}"))
			return FALSE;
		
		return session()->get("Sess::List:{$function}");
	}
	
}
