<?php

namespace App\ViewModels;

use App\ViewModels\Attributes\attrStatus;
use App\ViewModels\Attributes\attrActionBar;
use App\ViewModels\Attributes\attrAllowAction;
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
class StoreMapViewModel extends Fluent
{
	use attrStatus, attrActionBar, attrAllowAction;
	
	public function __construct()
	{
		$this->function		= Functions::STORE_MAP;
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
		$this->action	= $action;
		$this->success();
		
		#$this->_setOptions();
	}
	
	/* Form所屬的參數選項
	 * @params:  
	 * @return: void
	 */
	private function _setOptions()
	{
	}
	
	/* 查詢選項
	 * @params:  
	 * @return: void
	 */
	private function _setSearchMode()
	{
		$type = [
			'info'		=> '門店資訊', 
			'dayOff'	=> '店休資訊',
		];
		$this->set('options.mode.type', $type);
	}
	
	/* Form submit action
	 * @params: 
	 * @return: string
	 */
	public function getFormAction($isExport = FALSE) : string
    {
		#因export不會有頁面
		$action = ($isExport) ? FormAction::EXPORT : $this->action;
		$brandCode = $this->brand->code();
		
		return match($action)
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
	public function keepSearchData($type = 'info', $stDate = '')
    {
		if (empty($stDate) && empty($endDate) && empty($shopType))
			$shopType[] = 1; #Default直營
		
		$this->set('search.type', $type);
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
	
	public function isDataEmpty()
	{
		#改判別token即可
		if (empty($this->statistics['exportToken']))
			return TRUE;
		else
			return FALSE;
	}
	
	public function hasExportData()
	{
		if ($this->isDataEmpty() OR empty($this->statistics['exportToken']))
			return FALSE;
		else
			return TRUE;
	}
	
	public function getBrandCode()
	{
		return $this->brand->code();
	}
}