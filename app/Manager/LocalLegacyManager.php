<?php

namespace App\Manager;

use App\Facades\StoreManager;
use App\Manager\Repositories\LocalLegacyRepository;
use App\Enums\OpCenter;
use App\Enums\Brand;
use App\Enums\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

/* Local extra order */
#取Local DB追加(因查詢舊系統效能差),正常狀況不需另外取
#但需待未來都在新系統建單,此功能就可以不用,故獨立寫,以方便未來抽離
class LocalLegacyManager
{
	public function __construct(protected LocalLegacyRepository $_repository)
	{
	}
	
	public function getFactoryNoByOpCenter($brand, $authOpCenter)
	{
		if ($brand == Brand::BAFANG)
		{
			$authFactoryNo = [];
			
			if (in_array(OpCenter::TAIPEI->value, $authOpCenter))
				$authFactoryNo[] = Factory::TP->value;
			
			if (in_array(OpCenter::KAOHSIUNG->value, $authOpCenter))
				$authFactoryNo[] = Factory::KH->value;
			
			return $authFactoryNo;
		}
		else if ($brand == Brand::BUYGOOD)
			return [Factory::TS->value, Factory::RL->value];
		else
			return [];
	}
	
	/* 取追加
	 * @params: datetime
	 * @params: datetime
	 * @return: array
	 */
	public function getExtraDataByProduct($brand, $opCenter, $stDate, $endDate, $productCodes)
	{
		#架構不同,用factoryNo判別
		$factoryNos = $this->getFactoryNoByOpCenter($brand, $opCenter);
		
		#這裏的storeNo已是storeKey格式
		$data = $this->_repository->getExtraData($brand, $factoryNos, $stDate, $endDate, $productCodes);
		
		return $data;
	}
	
	/* 取追加ByPosId
	 * @params: datetime
	 * @params: datetime
	 * @return: array
	 */
	public function getExtraDataByStore($brand, $opCenter, $stDate, $endDate, $storeKeys)
	{
		/*[
			"shortCode" => "3615"
			"productName" => "紹辣醬"
			"storeNo" => "1001"
			"expectedDate" => "2026-06-01"
			"qty" => "50.00"
			"amount" => "4500.00"
			"factoryNo" => "TW_TP"
			"factoryName" => "淡水總廠"
			"isExtra" => true
		]
		*/
		$factoryNos = $this->getFactoryNoByOpCenter($brand, $opCenter);
		
		$data = $this->_repository->getExtraData($brand, $factoryNos, $stDate, $endDate, FALSE);
		
		#這裏的storeNo已是storeKey格式
		$data = collect($data)->map(function($item, $key){
			$item['storeKey'] 		= StoreManager::buildStoreKey($item['storeNo']);
			
			return $item;
		})->filter(function($item, $key) use($storeKeys){
			return in_array($item['storeKey'], $storeKeys);
		})->toArray();
			
		return $data;
	}
	
	
}