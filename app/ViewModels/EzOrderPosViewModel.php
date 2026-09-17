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

class EzOrderPosViewModel extends Fluent
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
			'ez'	=> '八方點', 
			'ezpos'	=> '八方點及銷售', 
		];
		$this->set('options.type', $type);
		
		$by = [
			'store'	=> '依門店', 
			'area'	=> '依區域', 
		];
		$this->set('options.by', $by);
		
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
			FormAction::LIST	=> route(Str::replace('?', $brandCode, '?.ezorder_pos.search')),
			FormAction::EXPORT	=> route(Str::replace('?', $brandCode, '?.ezorder_pos.export'), ['token' => $this->statistics['exportToken']]),
		};
	}
	
	/* Keep search data of form
	 * @params: string
	 * @params: string
	 * @params: int
	 * @return: string
	 */
	public function keepSearchData($type = 'ez', $by = 'store', $stDate = NULL, $endDate = NULL, $areaIds = [], $storeName = '')
    {
		#Init default type
		$today = Carbon::now()->format('Y-m-d');
		$thisMonth = Carbon::now()->format('Y-m');
		
		$this->set('search.type', $type);
		$this->set('search.by', $by);
		$this->set('search.stDate', $stDate ?? $today);
		$this->set('search.endDate', $endDate ?? $today);
		$this->set('search.areaIds', $areaIds);
		$this->set('search.storeName', $storeName);
		$this->set('search.today', $today);
		$this->set('search.thisMonth', $thisMonth);
	}
	
	/* Partial view
	 * @params: string
	 * @return: string
	 */
	public function getPartialView()
	{
		$by = $this->get('search.by', 'store');
		
		return match($by)
		{
			'store'	=> 'ezorder_pos.store',
			'area'	=> 'ezorder_pos.area',
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
		$response['hasResult'] = data_get($this->statistics, 'hasResult', FALSE);
		
		$by = data_get($this->statistics, 'by', '');
		$response['hasFilter'] = ($by == 'store');
		
		return $response;
	}
}