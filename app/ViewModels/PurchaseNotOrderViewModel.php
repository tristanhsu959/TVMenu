<?php

namespace App\ViewModels;

use App\Facades\AppManager;
use App\Services\PurchaseNotOrderService;
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

class PurchaseNotOrderViewModel extends Fluent
{
	use attrStatus, attrActionBar, attrAllowAction, attrResponse;
	
	public function __construct(protected PurchaseNotOrderService $_service)
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
		$fillingTitle = ($this->brand == Brand::BAFANG) ? '所有餡皮' : '炸雞腿．炸排骨';
		$type = [
			'filling'	=> $fillingTitle,
			'product'	=> '自選產品',
		];
		$this->set('options.type', $type);
		
		$calc = [
			'whereall'	=> '全部符合未訂', 
			'whereany'	=> '任一符合未訂', 
		];
		$this->set('options.calc', $calc);
		
		$by = [
			'keyword'	=> '關鍵字查詢',
			'category'	=> '分類查詢', 
		];
		$this->set('options.by', $by);
		
		$areaList = $this->getPurchaseAreaOptions($this->brand);
		$this->set('options.areaList', $areaList);
		
		list($category, $products) = $this->_service->getProductOptions($this->brand);
		$this->set('options.category', $category);
		$this->set('options.products', $products); 
		
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
			FormAction::LIST	=> route(Str::replace('?', $brandCode, '?.purchase_not_order.search')),
			FormAction::EXPORT	=> route(Str::replace('?', $brandCode, '?.purchase_not_order.export'), ['token' => $this->statistics['exportToken']]),
		};
	}
	
	/* Keep search data of form
	 * @params: string
	 * @params: string
	 * @params: string
	 * @params: string
	 * @return: array
	 */
	public function keepSearchData($searchType = 'filling', $searchCalc = 'whereall', $searchStDate = NULL, /* $searchEndDate = NULL, */
						$searchAreaIds = [], $searchBy = 'keyword', $searchKeyword = '', 
						$searchCategory = '', $searchShortCodes = [])
    {
		$today = Carbon::tomorrow()->format('Y-m-d');
		$searchStDate	= $searchStDate ?? $today;
		#$searchEndDate 	= $searchEndDate ?? $today;
		
		$this->set('search.type', $searchType);
		$this->set('search.calc', $searchCalc);
		$this->set('search.stDate', $searchStDate);
		#$this->set('search.endDate', $searchEndDate);
		$this->set('search.areaIds', $searchAreaIds);
		$this->set('search.by', $searchBy);
		$this->set('search.keyword', $searchKeyword);
		$this->set('search.category', $searchCategory);
		$this->set('search.shortCodes', $searchShortCodes);
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
			'filling' => 'purchase_not_order.store',
			'product' => 'purchase_not_order.store',
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
		
		#filter tool
		$type = data_get($this->statistics, 'type', NULL);
		$response['hasFilter'] = ($type == 'filling' OR $type == 'product');
		$response['hasResult'] = data_get($this->statistics, 'hasResult', FALSE);
		
		return $response;
	}
}