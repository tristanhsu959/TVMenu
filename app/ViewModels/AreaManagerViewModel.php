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

#督導維護
class AreaManagerViewModel extends Fluent
{
	use attrStatus, attrActionBar, attrAllowAction;
	
	public function __construct()
	{
		$this->function		= Functions::AREA_MANAGER;
		$this->action 		= FormAction::LIST; 
		$this->backRoute 	= 'area_manager';
		$this->success();
		$this->list = NULL;
	}
	
	/* initialize
	 * @params: enum
	 * @params: enum
	 * @return: void
	 */
	public function initialize($action)
	{
		$this->action = $action;
		$this->success();
		$this->_setOptions();
	}
	
	/* Form所屬的參數選項
	 * @params:  
	 * @return: void
	 */
	private function _setOptions()
	{
		$brandList	= Brand::toArray();
		$this->set('options.brandList', $brandList);
		
		$areaList 	= $this->getAllAreaOptions(); #不分區, 全開
		$this->set('options.areaList', $areaList);
	}
	
	/* Form submit action
	 * @params: 
	 * @return: string
	 */
	public function getFormAction($formAction) : string
    {
		return match($formAction)
		{
			FormAction::LIST	=> route('area_manager.search'),
			FormAction::UPDATE	=> route('area_manager.update'),
			FormAction::EXPORT	=> route('area_manager.export', ['token' => data_get($this->list, 'exportToken', FALSE)]),
		};
	}
	
	/* Keep search data of form
	 * @params: string
	 * @params: string
	 * @params: int
	 * @return: string
	 */
	public function keepSearchData($brandId = Brand::BAFANG->value, $areaIds = [])
    {
		$this->set('search.brandId', $brandId);
		$this->set('search.areaIds', $areaIds);
	}
	
	/* Partial view
	 * @params: string
	 * @return: string
	 */
	public function getPartialView()
	{
		$action = $this->action;
		
		return match($action)
		{
			FormAction::LIST	=> 'area_manager.list',
		};
	}
	
	/* Output js */
	public function searchFormData()
	{
		$this->set('search.formAction',  $this->getFormAction(FormAction::LIST));
		
		return $this->only('search', 'options');
	}
	
	/*因與統計不同, 不使用trait response*/
	public function responseData()
	{
		$exportAction = data_get($this->list, 'exportToken', FALSE);
		
		$response['status'] 		= $this->status();
		$response['msg'] 			= $this->msg();
		$response['hasResult'] 		= data_get($this->list, 'hasResult', FALSE);
		$response['isInit'] 		= is_null($this->list);
		$response['exportAction']	= empty($exportAction) ? '' : $this->getFormAction(FormAction::EXPORT);
		$response['updateAction']	= $this->getFormAction(FormAction::UPDATE);
		
		return $response;
	}
	
	public function listData()
	{
		return data_get($this->list, 'storeList', []);
	}
}