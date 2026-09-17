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

class PurchaseReportViewModel extends Fluent
{
	use attrStatus, attrActionBar, attrAllowAction, attrResponse;
	
	public function __construct()
	{
		$this->function		= NULL;
		$this->action 		= FormAction::LIST; 
		$this->backRoute 	= '';
		$this->success();
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
			'performance'	=> '營運概況',
			'employeePr'	=> '員購公關',
			'extraOrder'	=> '追加',
		];
		$this->set('options.type', $type);
		
		$brand = [
			Brand::BAFANG->value	=> Brand::BAFANG->label(),
			Brand::LUOBO->value		=> Brand::LUOBO->label(),
		];
		$this->set('options.brand', $brand);
		
		$opCenterList 	= $this->getPurchaseOpCenterOptions($this->brand);
		$areaList 		= $this->getPurchaseAreaOptions($this->brand);
		
		$this->set('options.opCenterList', $opCenterList);
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
			FormAction::LIST	=> route(Str::replace('?', $brandCode, '?.purchase_report.search')),
			FormAction::EXPORT	=> route(Str::replace('?', $brandCode, '?.purchase_report.export'), ['token' => $this->statistics['exportToken']]),
		};
	}
	
	/* Keep search data of form
	 * @params: string
	 * @params: string
	 * @params: string
	 * @return: array
	 */
	public function keepSearchData($searchType = 'performance', $searchBrand = Brand::BAFANG->value, $searchStDate = NULL, $searchEndDate = NULL, $searchOpCenterIds = [], $searchAreaIds = [], $searchProductCodes = [])
    {
		$today = Carbon::now()->format('Y-m-d');
		
		$this->set('search.type', $searchType);
		$this->set('search.brand', $searchBrand);
		$this->set('search.stDate', $searchStDate ?? $today);
		$this->set('search.endDate', $searchEndDate ?? $today);
		$this->set('search.opCenterIds', $searchOpCenterIds);
		$this->set('search.areaIds', $searchAreaIds);
		$this->set('search.productCodes', $searchProductCodes);
		$this->set('search.today', $today); 
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
			'performance'	=> 'purchase_report.performance',
			'employeePr'	=> 'purchase_report.employee_pr',
			'extraOrder'	=> 'purchase_report.extra_order',
		};
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
		
		$data = data_get($this->statistics, 'report', []);
		$response['hasResult'] = data_get($this->statistics, 'hasResult', FALSE);
		
		return $response;
	}
}