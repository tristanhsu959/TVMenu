<?php

namespace App\ViewModels;

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

class MonthlyFillingViewModel extends Fluent
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
		$this->_setSearchMode();
	}
	
	/* 查詢選項
	 * @params:  
	 * @return: void
	 */
	private function _setSearchMode()
	{
		$type = [
			'store'		=> '依門店(餡量)', 
			'factory'	=> '依工廠(月總量．月均量)',
		];
		$this->set('options.type', $type);
		
		#查詢區間
		$range = [
			'year'		=> '本年度', 
			'month'		=> '月區間', 
			'date'		=> '日區間',
		];
		$this->set('options.range', $range);
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
			FormAction::LIST	=> route(Str::replace('?', $brandCode, '?.monthly_filling.search')),
			FormAction::EXPORT	=> route(Str::replace('?', $brandCode, '?.monthly_filling.export'), ['token' => $this->statistics['exportToken']]),
		};
	}
	
	/* Keep search data of form
	 * @params: string
	 * @params: string
	 * @params: string
	 * @return: array
	 */
	public function keepSearchData($searchStDate = '', $searchEndDate = '', $searchType = 'store', $searchRange = 'year')
    {
		$this->set('search.stDate', $searchStDate);
		$this->set('search.endDate', $searchEndDate);
		$this->set('search.type', $searchType);
		$this->set('search.range', $searchRange);
		$this->set('search.currentDate', Carbon::tomorrow()->format('Y-m-d'));
		#$this->set('search.currentDate', Carbon::now()->format('Y-m-d')); 
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
			'store'		=> 'monthly_filling.store',
			'factory'	=> 'monthly_filling.factory',	 
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
		
		$data = data_get($this->statistics, 'data', []);
		$response['hasResult'] = !empty($data);
		
		return $response;
	}
}