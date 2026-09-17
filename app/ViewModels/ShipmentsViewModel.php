<?php

namespace App\ViewModels;

use App\Facades\AppManager;
use App\Services\ShipmentsService;
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

class ShipmentsViewModel extends Fluent
{
	use attrStatus, attrActionBar, attrAllowAction, attrResponse;
	
	public function __construct(protected ShipmentsService $_service)
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
		$this->_setSearchMode();
		
		$areaList = $this->getPurchaseAreaOptions($this->brand);
		$this->set('options.areaList', $areaList);
		
		list($category, $products) = $this->_service->getProductOptions($this->brand);
		$this->set('options.category', $category);
		$this->set('options.products', $products); 
		
	}
	
	/* 查詢選項
	 * @params:  
	 * @return: void
	 */
	private function _setSearchMode()
	{
		$type = [
			'total'		=> '總量', 
			'status'	=> '門店訂貨狀況',
		];
		$this->set('options.type', $type);
		
		$by = [
			'store'		=> '依門店', #option of total
			'factory'	=> '依工廠', #option of total
		];
		$this->set('options.by', $by);
		
		$calc = [
			'day'	=> '以日計算', 
			'month'	=> '以月計算',
		];
		$this->set('options.calc', $calc);

		$where = [
			'keyword'	=> '關鍵字查詢',
			'category'	=> '分類查詢', 
		];
		$this->set('options.where', $where);
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
			FormAction::LIST	=> route(Str::replace('?', $brandCode, '?.shipments.search')),
			FormAction::EXPORT	=> route(Str::replace('?', $brandCode, '?.shipments.export'), ['token' => $this->statistics['exportToken']]),
		};
	}
	
	/* Keep search data of form
	 * @params: string
	 * @params: string
	 * @params: string
	 * @params: string
	 * @return: array
	 */
	public function keepSearchData($searchType = 'total', $searchBy = 'store', $searchCalc = 'day', $searchStDate = NULL, $searchEndDate = NULL,
						$searchAreaIds = [], $searchWhere = 'keyword', $searchKeyword = '', 
						$searchCategory = '', $searchShortCodes = [], $searchStoreName = '')
    {
		$today = now()->format('Y-m-d');
		$searchStDate	= $searchStDate ?? $today;
		$searchEndDate 	= $searchEndDate ?? $today;
		
		$this->set('search.type', $searchType);
		$this->set('search.by', $searchBy);
		$this->set('search.calc', $searchCalc);
		$this->set('search.stDate', $searchStDate);
		$this->set('search.endDate', $searchEndDate);
		$this->set('search.areaIds', $searchAreaIds);
		$this->set('search.where', $searchWhere);
		$this->set('search.keyword', $searchKeyword);
		$this->set('search.category', $searchCategory);
		$this->set('search.shortCodes', $searchShortCodes);
		$this->set('search.storeName', $searchStoreName);
		$this->set('search.tomorrow', Carbon::tomorrow()->format('Y-m-d'));
	}
	
	/* Partial view
	 * @params: string
	 * @return: string
	 */
	public function getPartialView()
	{
		$type 	= $this->get('search.type', NULL);
		$by		= $this->get('search.by', NULL);
		
		$typeBy = ($type == 'status') ? $type : "{$type}:{$by}";
		
		return match($typeBy)
		{
			'total:store'	=> 'shipments.store',
			'total:factory'	=> 'shipments.factory',	 
			'status'		=> 'shipments.status',
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