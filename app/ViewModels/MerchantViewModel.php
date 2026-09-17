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

#門店資訊
class MerchantViewModel extends Fluent
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
	 * @params: enum
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
			'info'		=> '門店資訊', 
			'dayOff'	=> '店休資訊',
		];
		
		$this->set('options.type', $type);
		
		$areaList = $this->getAllAreaOptions(); #不分區, 全開
		$this->set('options.areaList', $areaList);
	}
	
	/* Form submit action
	 * @params: 
	 * @return: string
	 */
	public function getFormAction($formAction) : string
    {
		#因export不會有頁面
		$brandCode = $this->brand->code();
		
		return match($formAction)
		{
			FormAction::LIST	=> route(Str::replace('?', $brandCode, '?.merchant.search')),
			FormAction::EXPORT	=> route(Str::replace('?', $brandCode, '?.merchant.export'), ['token' => $this->statistics['exportToken']]),
		};
	}
	
	/* Keep search data of form
	 * @params: string
	 * @params: string
	 * @params: int
	 * @return: string
	 */
	public function keepSearchData($type = 'info', $stDate = '', $areaIds = [])
    {
		$this->set('search.type', $type);
		$this->set('search.areaIds', $areaIds);
		$this->set('search.stDate', $stDate);
		$this->set('search.today', Carbon::now()->format('Y-m-d'));
		$this->set('search.tomorrow', Carbon::tomorrow()->format('Y-m-d'));
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
			'info'		=> 'merchant.info',
			'dayOff'	=> 'merchant.dayoff',	 
		};
	}
	
	/* Output js */
	public function searchFormData()
	{
		$this->set('search.formAction',  $this->getFormAction(FormAction::LIST));
		
		return $this->only('search', 'options');
	}
	
	/*共用view所需的data*/
	public function responseData()
	{
		$response = $this->responseBaseData();
		$response['hasResult'] 	= data_get($this->statistics, 'hasResult', FALSE);
		#顯示列表判別用
		$response['dayoffCount']= data_get($this->statistics, 'dayoffCount', 0);
		
		$type = data_get($this->statistics, 'type', FALSE);
		$response['hasFilter'] 	= ($type == 'info');
		
		return $response;
	}
}