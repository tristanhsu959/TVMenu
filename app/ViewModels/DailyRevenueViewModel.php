<?php

namespace App\ViewModels;

use App\Facades\AppManager;
use App\ViewModels\Attributes\attrStatus;
use App\ViewModels\Attributes\attrActionBar;
use App\ViewModels\Attributes\attrAllowAction;
use App\ViewModels\Attributes\attrResponse;
use App\Enums\Brand;
use App\Enums\Area;
use App\Enums\Functions;
use App\Enums\FormAction;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Fluent;

class DailyRevenueViewModel extends Fluent
{
	use attrStatus, attrActionBar, attrAllowAction, attrResponse;
	
	public function __construct()
	{
		$this->function		= NULL;
		$this->action 		= FormAction::LIST; 
		$this->backRoute 	= '';
		$this->success();
		$this->statistics = [];
	}
	
	/* initialize
	 * @params: enum
	 * @params: string
	 * @params: string
	 * @return: void
	 */
	public function initialize($brand , $function)
	{
		$this->brand	= $brand;
		$this->function = $function;
		$this->statistics = [];
		
		$this->_setOptions();
	}
	
	/* Form所屬的參數選項
	 * @params:  
	 * @return: void
	 */
	private function _setOptions()
	{
		$type = [
			'day'	=> '單日', #原計算方式(By Day)
			'range'	=> '區間',
			'aov'	=> '客單統計', #Average Order Value
		];
		
		if ($this->brand == Brand::FJVEGGIE)
			unset($type['aov']);
		
		$this->set('options.type', $type);
		
		$calc = [
			'hourly'		=> '時段營收',
			'dailyClosing'	=> '門店日結(不含今日)',
		];
		$this->set('options.calc', $calc);
		
		#根據poserp.shop_kind
		$this->set('options.storeType', config('web.sales.shop.type'));
		
		$areaList = $this->getSalesAreaOptions();
		$this->set('options.areaList', $areaList);
	}
	
	/* Form submit action
	 * @params: 
	 * @return: string
	 */
	public function getFormAction($formAction) : string
    {
		$brandCode = $this->brand->code();
		
		return match($formAction)
		{
			FormAction::LIST	=> route(Str::replace('?', $brandCode, '?.daily_revenue.search')),
			FormAction::EXPORT	=> route(Str::replace('?', $brandCode, '?.daily_revenue.export'), ['token' => $this->statistics['exportToken']]),
		};
	}
	
	/* Keep search data of form
	 * @params: string
	 * @params: string
	 * @params: int
	 * @return: string
	 */
	public function keepSearchData($type = 'day', $calc = [], $stDate = NULL, $endDate = NULL, $storeType = [], $areaIds = [], $storeName = '')
    {
		#Init default type
		$yesterday = Carbon::yesterday()->format('Y-m-d');
		$today = Carbon::now()->format('Y-m-d');
		$thisMonth = Carbon::now()->format('Y-m');
		
		#依brand預設不同
		$defaultStoreTypes = ($this->brand == Brand::BAFANG) ? [1] : [1, 2];
		
		if (empty($stDate) && empty($endDate) && empty($storeType))
			$storeType =  $defaultStoreTypes; 
		
		$this->set('search.type', $type);
		$this->set('search.calc', $calc);
		$this->set('search.stDate', $stDate ?? $today);
		$this->set('search.endDate', $endDate ?? $today);
		$this->set('search.storeType', $storeType);
		$this->set('search.areaIds', $areaIds);
		$this->set('search.storeName', $storeName);
		$this->set('search.yesterday', $yesterday);
		$this->set('search.today', $today);
		$this->set('search.thisMonth', $thisMonth);
		$this->set('search.defaultStoreTypes', $defaultStoreTypes);
	}
	
	/* Partial view
	 * @params: string
	 * @return: string
	 */
	public function getPartialView()
	{
		$type = $this->get('search.type', NULL);
		
		return match($type)
		{
			'day'	=> 'daily_revenue.day',
			'range'	=> 'daily_revenue.range',
			'aov'	=> 'daily_revenue.aov',	 
		};
	}
	
	/* Output js */
	public function searchFormData()
	{
		$this->set('search.formAction',  $this->getFormAction(FormAction::LIST));
		
		return $this->only('search', 'options');
	}
	
	/*依不同功能的額外資訊,共用的在baseResponse */
	public function responseData()
	{
		$response = $this->responseBaseData();
		#$type = data_get($this->statistics, 'type', NULL);
		
		$response['hasResult'] = data_get($this->statistics, 'hasResult', FALSE);
		#$response['type'] = $type;
		
		return $response;
	}
}