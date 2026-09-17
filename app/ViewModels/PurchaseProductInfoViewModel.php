<?php

namespace App\ViewModels;

use App\Facades\AppManager;
use App\Services\PurchaseProductInfoService;
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

class PurchaseProductInfoViewModel extends Fluent
{
	use attrStatus, attrActionBar, attrAllowAction, attrResponse;
	
	public function __construct(protected PurchaseProductInfoService $_service)
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
			'info' => '產品資訊', 
		];
		$this->set('options.type', $type);
		
		#string key避免排序問題
		$productTypes = [
			'preorder'	=> '預購產品', 
			'supplier' 	=> '供應商產品', 
		];
		$this->set('options.productTypes', $productTypes);
		
		$factoryList = $this->_service->getFactoryOptions($this->brand);
		$this->set('options.factoryList', $factoryList);
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
			FormAction::LIST	=> route(Str::replace('?', $brandCode, '?.purchase_product_info.search')),
			FormAction::EXPORT	=> route(Str::replace('?', $brandCode, '?.purchase_product_info.export'), ['token' => $this->statistics['exportToken']]),
		};
	}
	
	/* Keep search data of form
	 * @params: string
	 * @params: string
	 * @params: string
	 * @params: string
	 * @return: array
	 */
	public function keepSearchData($searchType = 'info', $searchProductTypes = [], $searchFactoryIds = [], $searchOffShelf = FALSE, $searchShortCode = '', $searchProductName = '')
    {
		$this->set('search.type', $searchType);
		$this->set('search.productTypes', $searchProductTypes);
		$this->set('search.factoryIds', $searchFactoryIds);
		$this->set('search.offShelf', $searchOffShelf);
		$this->set('search.shortCode', $searchShortCode);
		$this->set('search.productName', $searchProductName);
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
			'info'	=> 'purchase_product_info.info',
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
		
		return $response;
	}
}