<?php

namespace App\ViewModels;

use App\Facades\AppManager;
use App\ViewModels\Attributes\attrStatus;
use App\ViewModels\Attributes\attrActionBar;
use App\ViewModels\Attributes\attrAllowAction;
use App\ViewModels\Attributes\attrResponse;
use App\Enums\Brand;
use App\Enums\Functions;
use App\Enums\FormAction;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Fluent;

#銷售活動統計
class SaleEventsViewModel extends Fluent
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
		$this->brand		= $brand;
		$this->function 	= $function;
		$this->statistics 	= [];
		
		$this->_setOptions();
	}
	
	/* Form所屬的參數選項
	 * @params:  
	 * @return: void
	 */
	private function _setOptions()
	{
		$type = [
			'moonFestival'	=> '中秋節活動', 
		];
		$this->set('options.type', $type);
		
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
			FormAction::LIST	=> route(Str::replace('?', $brandCode, '?.sale_events.search')),
			FormAction::EXPORT	=> route(Str::replace('?', $brandCode, '?.sale_events.export'), ['token' => $this->statistics['exportToken']]),
		};
	}
	
	/* Keep form search data
	 * @params: int
	 * @params: date
	 * @params: date
	 * @return: void
	 */
	public function keepSearchData($searchType = 'moonFestival', $searchStDate = NULL, $searchEndDate = NULL, $searchAreaIds = [])
    {
		$brandId	= $this->brand->value;
		$config		= config("web.sales.events.{$brandId}.moonFestival");
		$eventStDate	= $config['startDate'];
		$eventEndDate 	= $config['endDate'];
		
		$today = now()->format('Y-m-d');
		
		$this->set('search.type', $searchType); 
		$this->set('search.stDate', $searchStDate ?? $today); 
		$this->set('search.endDate', $searchEndDate ?? $today);
		$this->set('search.areaIds', $searchAreaIds);
		$this->set('search.today', $today);
		$this->set('search.eventStDate', $eventStDate);
		$this->set('search.eventEndDate', $eventEndDate);
	}
	
	/* Output js */
	public function searchFormData()
	{
		$this->set('search.formAction',  $this->getFormAction(FormAction::LIST));
		
		return $this->only('search', 'options');
	}
	
	/*有額外資訊能獨立加入,故要寫在Base*/
	public function responseData()
	{
		$response = $this->responseBaseData();
		
		$response['hasResult'] = data_get($this->statistics, 'hasResult', FALSE);
		$response['hasFilter'] = !empty($response['hasResult']); 
		
		return $response;
	}
	
	public function moonFestivalData()
	{
		$response['store'] 		= data_get($this->statistics, 'store', []);
		$response['dayRange'] 	= data_get($this->statistics, 'dayRange', []);
		$response['productList']= data_get($this->statistics, 'productList', []);
		
		return $response;
	}
}