<?php

namespace App\Manager;

use App\Facades\PurchaseManager;
use App\Manager\Repositories\LegacyRepository;
use App\Enums\Brand;
use App\Enums\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

/* Old Order sys Common */
class LegacyManager
{
	public function __construct(protected LegacyRepository $_repository)
	{
	}
	
	public function getFactoryNo($brandId)
	{
		$brand = Brand::tryFrom($brandId);
		if ($brandId == Brand::BAFANG->value)
			return [Factory::TP->value, Factory::KH->value];
		else
			return [Factory::TS->value, Factory::RL->value];
	}
	
	/* 取全部追加(Save to local scheduling會用到)
	 * @params: datetime
	 * @params: datetime
	 * @return: array
	 */
	public function getExtraData($stDate, $endDate)
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
		
		#***** 排程會呼叫此Function getExtraData *****
		$bafang 	= $this->getExtraDataByBafang($stDate, $endDate, FALSE); #false for all
		$buygood	= $this->getExtraDataByBuygood($stDate, $endDate, FALSE);
		
		$result = collect($bafang)->merge($buygood)->toArray();
		
		return $result;
	}
	
	/* 取追加
	 * @params: datetime
	 * @params: datetime
	 * @return: array
	 */
	public function getExtraDataByProduct($brand, $stDate, $endDate, $productCodes)
	{
		if ($brand == Brand::BAFANG)
			$data = $this->getExtraDataByBafang($stDate, $endDate, $productCodes);
		else if ($brand == Brand::BUYGOOD)
			$data = $this->getExtraDataByBuygood($stDate, $endDate, $productCodes);
		else
			$data = [];
		
		return $data;
	}
	
	/* 取追加(先全取再由各自功能過濾門店或其它條件)
	 * @params: datetime
	 * @params: datetime
	 * @return: array
	 */
	public function getExtraDataByBafang($stDate, $endDate, $productCodes)
	{
		$tp = $this->_repository->getTpExtraData($stDate, $endDate, $productCodes);
		$kh = $this->_repository->getKhExtraData($stDate, $endDate, $productCodes);
		
		#storeNo維持原樣不影響
		$tp = $tp->map(function($item, $key){
			$item['factoryNo'] 		= Factory::TP->value;
			$item['factoryName'] 	= Factory::TP->label();
			$item['expectedDate']	= Carbon::parse($item['expectedDate'])->format('Y-m-d');
			$item['isExtra'] 		= TRUE; #判別area權限用,因這裏沒有區域定義
			return $item;
		});
		
		$kh = $kh->map(function($item, $key){
			$item['factoryNo'] 		= Factory::KH->value;
			$item['factoryName'] 	= Factory::KH->label();
			$item['expectedDate']	= Carbon::parse($item['expectedDate'])->format('Y-m-d');
			$item['isExtra'] 		= TRUE;
			return $item;
		});
		
		$result = $tp->merge($kh)->toArray();
		
		return $result;
	}
	
	/* 取追加
	 * @params: datetime
	 * @params: datetime
	 * @return: array
	 */
	public function getExtraDataByBuygood($stDate, $endDate, $productCodes)
	{
		$ts = $this->_repository->getTsExtraData($stDate, $endDate, $productCodes);
		$rl = $this->_repository->getRlExtraData($stDate, $endDate, $productCodes);
		
		#storeNo維持原樣不影響
		$ts = $ts->map(function($item, $key){
			$item['factoryNo'] 		= Factory::TS->value;
			$item['factoryName'] 	= Factory::TS->label();
			$item['expectedDate']	= Carbon::parse($item['expectedDate'])->format('Y-m-d');
			$item['isExtra'] 		= TRUE;
			return $item;
		});
		
		$rl = $rl->map(function($item, $key){
			$item['factoryNo'] 		= Factory::RL->value;
			$item['factoryName'] 	= Factory::RL->label();
			$item['expectedDate']	= Carbon::parse($item['expectedDate'])->format('Y-m-d');
			$item['isExtra'] 		= TRUE;
			return $item;
		});
		
		$result = $ts->merge($rl)->toArray();
		
		return $result;
	}
	
	/* 取追加ByPosId
	 * @params: datetime
	 * @params: datetime
	 * @return: array
	 */
	public function getExtraDataByStore($brand, $stDate, $endDate, $storeKey)
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
		#先取全部再過濾
		if ($brand == Brand::BAFANG)
			$data = $this->getExtraDataByBafang($stDate, $endDate, FALSE);
		else if ($brand == Brand::BUYGOOD)
			$data = $this->getExtraDataByBuygood($stDate, $endDate, FALSE);
		else
			$data = [];
		
		$data = collect($data)->map(function($item, $key){
			$temp['expectedDate'] 	= $item['expectedDate'];
			$temp['shortCode'] 		= $item['shortCode'];
			$temp['productName'] 	= $item['productName'];
			$temp['qty'] 			= $item['qty'];
			$temp['amount'] 		= $item['amount'];
			$temp['storeKey'] 		= PurchaseManager::buildStoreKey($item['storeNo']);
			
			return $temp;
		})->filter(function($item, $key) use($storeKey){
			return $item['storeKey'] == $storeKey;
		})->toArray();
			
		return $data;
	}
	
	#======================= 員購/公關 =======================
	
	/* 員購訂單
	 * @params: datetime
	 * @params: datetime
	 * @return: array
	 */
	public function getEmployeeData($brand, $stDate, $endDate, $factoryNos)
	{
		#公關員購可套用同一邏輯
		$brandId = $brand->value;
		$accNos = config("web.purchase.store.employee.{$brandId}");
		
		#因為是獨立的DB
		if ($brand == Brand::BAFANG)
			$data = $this->getBafangDataByAccNo($stDate, $endDate, $accNos, $factoryNos);
		else if ($brand == Brand::BUYGOOD)
			$data = $this->getBuygoodDataByAccNo($stDate, $endDate, $accNos, $factoryNos);
		else
			$data = [];
		
		return $data;
	}
	
	
	/* 公關訂單
	 * @params: datetime
	 * @params: datetime
	 * @return: array
	 */
	public function getPRData($brand, $stDate, $endDate, $factoryNos)
	{
		#公關員購可套用同一邏輯
		$brandId = $brand->value;
		$accNos = config("web.purchase.store.pr.{$brandId}");
			
		if ($brand == Brand::BAFANG)
			$data = $this->getBafangDataByAccNo($stDate, $endDate, $accNos, $factoryNos);
		else if ($brand == Brand::BUYGOOD)
			$data = $this->getBuygoodDataByAccNo($stDate, $endDate, $accNos, $factoryNos);
		else
			$data = [];
		
		return $data;
	}
	
	/* 取追加(先全取再由各自功能過濾門店或其它條件)
	 * @params: datetime
	 * @params: datetime
	 * @return: array
	 */
	public function getBafangDataByAccNo($stDate, $endDate, $accNos, $factoryNos)
	{
		if (in_array('TW_TP', $factoryNos))
			$tp = $this->_repository->getTpDataByAccNo($stDate, $endDate, $accNos['TW_TP']);
		else
			$tp = collect([]);
		
		if (in_array('TW_KH', $factoryNos))
			$kh = $this->_repository->getKhDataByAccNo($stDate, $endDate, $accNos['TW_KH']);
		else
			$kh = collect([]);
		
		#storeNo維持原樣不影響
		$tp = $tp->map(function($item, $key){
			$item['factoryNo'] 		= Factory::TP->value;
			$item['factoryName'] 	= Factory::TP->label();
			$item['orderDate']		= Carbon::parse($item['orderDate'])->format('Y-m-d H:i:s');
			return $item;
		});
		
		$kh = $kh->map(function($item, $key){
			$item['factoryNo'] 		= Factory::KH->value;
			$item['factoryName'] 	= Factory::KH->label();
			$item['orderDate']		= Carbon::parse($item['orderDate'])->format('Y-m-d H:i:s');
			return $item;
		});
		
		$result = $tp->merge($kh)->toArray();
		
		return $result;
	}
	
	/* 取追加
	 * @params: datetime
	 * @params: datetime
	 * @return: array
	 */
	public function getBuygoodDataByAccNo($stDate, $endDate, $accNos)
	{
		if (in_array('TW_TS', $factoryNos))
			$ts = $this->_repository->getTsDataByAccNo($stDate, $endDate, $accNos['TW_TS']);
		else
			$ts = collect([]);
		
		if (in_array('TW_RL', $factoryNos))
			$rl = $this->_repository->getRlDataByAccNo($stDate, $endDate, $accNos['TW_RL']);
		else
			$rl = collect([]);
		
		#storeNo維持原樣不影響
		$ts = $ts->map(function($item, $key){
			$item['factoryNo'] 		= Factory::TS->value;
			$item['factoryName'] 	= Factory::TS->label();
			$item['orderDate']		= Carbon::parse($item['orderDate'])->format('Y-m-d H:i:s');
			return $item;
		});
		
		$rl = $rl->map(function($item, $key){
			$item['factoryNo'] 		= Factory::RL->value;
			$item['factoryName'] 	= Factory::RL->label();
			$item['orderDate']		= Carbon::parse($item['orderDate'])->format('Y-m-d H:i:s');
			return $item;
		});
		
		$result = $ts->merge($rl)->toArray();
		
		return $result;
	}
	
	
	#======================= 追加(含單頭單身) =======================
	/* 追加訂單
	 * @params: datetime
	 * @params: datetime
	 * @return: array
	 */
	public function getFullExtraData($brand, $stDate, $endDate, $factoryNos)
	{
		if ($brand == Brand::BAFANG)
			$data = $this->getBafangFullExtraData($stDate, $endDate, $factoryNos);
		else if ($brand == Brand::BUYGOOD)
			$data = $this->getBuygoodFullExtraData($stDate, $endDate, $factoryNos);
		else
			$data = [];
		
		return $data;
	}
	
	/* 取追加(先全取再由各自功能過濾門店或其它條件)
	 * @params: datetime
	 * @params: datetime
	 * @return: array
	 */
	public function getBafangFullExtraData($stDate, $endDate, $factoryNos)
	{
		if (in_array('TW_TP', $factoryNos))
			$tp = $this->_repository->getTpFullExtraData($stDate, $endDate);
		else
			$tp = collect([]);
		
		if (in_array('TW_KH', $factoryNos))
			$kh = $this->_repository->getKhFullExtraData($stDate, $endDate);
		else
			$kh = collect([]);
		
		#storeNo維持原樣不影響
		$tp = $tp->map(function($item, $key){
			$item['factoryNo'] 		= Factory::TP->value;
			$item['factoryName'] 	= Factory::TP->label();
			$item['orderDate']		= Carbon::parse($item['orderDate'])->format('Y-m-d H:i:s');
			return $item;
		});
		
		$kh = $kh->map(function($item, $key){
			$item['factoryNo'] 		= Factory::KH->value;
			$item['factoryName'] 	= Factory::KH->label();
			$item['orderDate']		= Carbon::parse($item['orderDate'])->format('Y-m-d H:i:s');
			return $item;
		});
		
		$result = $tp->merge($kh)->toArray();
		
		return $result;
	}
	
	/* 取追加
	 * @params: datetime
	 * @params: datetime
	 * @return: array
	 */
	public function getBuygoodFullExtraData($stDate, $endDate, $factoryNos)
	{
		if (in_array('TW_TS', $factoryNos))
			$ts = $this->_repository->getTsFullExtraData($stDate, $endDate);
		else
			$ts = collect([]);
		
		if (in_array('TW_RL', $factoryNos))
			$rl = $this->_repository->getRlFullExtraData($stDate, $endDate);
		else
			$rl = collect([]);
		
		#storeNo維持原樣不影響
		$ts = $ts->map(function($item, $key){
			$item['factoryNo'] 		= Factory::TS->value;
			$item['factoryName'] 	= Factory::TS->label();
			$item['orderDate']		= Carbon::parse($item['orderDate'])->format('Y-m-d H:i:s');
			return $item;
		});
		
		$rl = $rl->map(function($item, $key){
			$item['factoryNo'] 		= Factory::RL->value;
			$item['factoryName'] 	= Factory::RL->label();
			$item['orderDate']		= Carbon::parse($item['orderDate'])->format('Y-m-d H:i:s');
			return $item;
		});
		
		$result = $ts->merge($rl)->toArray();
		
		return $result;
	}
}