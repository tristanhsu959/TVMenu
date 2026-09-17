<?php

namespace App\ViewModels;

use App\Services\SalesSettingService;
use App\Enums\FormAction;
use App\Enums\Functions;
use App\Enums\Brand;
use App\ViewModels\Attributes\attrStatus;
use App\ViewModels\Attributes\attrActionBar;
use App\ViewModels\Attributes\attrAllowAction;
use Illuminate\Support\Arr;
use Illuminate\Support\Fluent;

class SalesSettingViewModel extends Fluent
{
	use attrStatus, attrActionBar, attrAllowAction;
	
	public function __construct(protected SalesSettingService $_service)
	{
		$this->function		= Functions::SALES_SETTING;
		$this->action 		= FormAction::LIST; 
		$this->backRoute 	= 'sales_setting';
		$this->success();
	}
	
	/* initialize
	 * @params: enum
	 * @return: void
	 */
	public function initialize($action)
	{
		#初始化各參數及Form Options
		$this->action	= $action;
		$this->success();
		$this->_setOptions();
	}
	
	/* Form所屬的參數選項
	 * @params:  
	 * @return: void
	 */
	private function _setOptions()
	{
		$this->set('options.brands', Brand::toArray()); 
		
		if ($this->action != FormAction::LIST)
			$this->set('options.products', $this->_service->getProductList()); 
	}
	
	/* Form submit action
	 * @params: 
	 * @return: string
	 */
	public function getFormAction() : string
    {
		return match($this->action)
		{
			FormAction::CREATE => route('sales_setting.create.post'),
			FormAction::UPDATE => route('sales_setting.update.post'),
		};
	}
	
	/* Keep form data
	 * @params: int
	 * @params: int
	 * @params: string
	 * @params: string
	 * @params: int
	 * @return: void
	 */
	public function keepFormData($id = 0, $brandId = Brand::BAFANG->value, $name = '', $status = TRUE, $productIds = [], $updateAt = '')
    {
		$this->set('formData.id', $id);
		$this->set('formData.brandId', $brandId);
		$this->set('formData.name', $name);
		$this->set('formData.status', $status);
		$this->set('formData.productIds', $productIds);
		$this->set('formData.updateAt', $updateAt);
	}
}