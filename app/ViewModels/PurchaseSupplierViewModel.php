<?php

namespace App\ViewModels;

use App\Facades\AppManager;
use App\Services\PurchaseSupplierService;
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

class PurchaseSupplierViewModel extends Fluent
{
	use attrStatus, attrActionBar, attrAllowAction, attrResponse;
	
	public function __construct(protected PurchaseSupplierService $_service)
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
			'total'	=> '總量', 
		];
		$this->set('options.type', $type);
		
		$where = [
			'keyword'	=> '關鍵字查詢',
			'category'	=> '分類查詢', 
		];
		$this->set('options.where', $where);
		
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
			FormAction::LIST	=> route(Str::replace('?', $brandCode, '?.supplier.search')),
			FormAction::EXPORT	=> route(Str::replace('?', $brandCode, '?.supplier.export'), ['token' => $this->statistics['exportToken']]),
		};
	}
	
	/* Keep search data of form
	 * @params: string
	 * @params: string
	 * @params: string
	 * @params: string
	 * @return: array
	 */
	public function keepSearchData($searchType = 'total', $searchStDate = NULL, $searchEndDate = NULL,
						$searchAreaIds = [], $searchWhere = 'keyword', $searchKeyword = '', 
						$searchCategory = '', $searchProductIds = [])
    {
		$today = now()->format('Y-m-d');
		$tomorrow = Carbon::tomorrow()->format('Y-m-d');
		
		$searchStDate	= $searchStDate ?? $tomorrow;
		$searchEndDate 	= $searchEndDate ?? $tomorrow;
		
		$this->set('search.type', $searchType);
		$this->set('search.stDate', $searchStDate);
		$this->set('search.endDate', $searchEndDate);
		$this->set('search.areaIds', $searchAreaIds);
		$this->set('search.where', $searchWhere);
		$this->set('search.keyword', $searchKeyword);
		$this->set('search.category', $searchCategory);
		$this->set('search.productIds', $searchProductIds);
		$this->set('search.today', $today);
		$this->set('search.tomorrow', $tomorrow);
	}
	
	/* Partial view
	 * @params: string
	 * @return: string
	 */
	public function getPartialView()
	{
		$type 	= $this->get('search.type', NULL);
		
		return match($type)
		{
			'total'	=> 'purchase_supplier.store',
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
		$response['hasFilter'] = ($type == 'store');
		$response['hasResult'] = data_get($this->statistics, 'hasResult', FALSE);
		
		return $response;
	}
}