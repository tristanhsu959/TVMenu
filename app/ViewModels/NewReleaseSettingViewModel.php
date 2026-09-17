<?php

namespace App\ViewModels;

use App\Services\NewReleaseSettingService;
use App\Enums\FormAction;
use App\Enums\Functions;
use App\Enums\Brand;
use App\ViewModels\Attributes\attrStatus;
use App\ViewModels\Attributes\attrActionBar;
use App\ViewModels\Attributes\attrAllowAction;
use Illuminate\Support\Arr;
use Illuminate\Support\Fluent;

class NewReleaseSettingViewModel extends Fluent
{
	use attrStatus, attrActionBar, attrAllowAction;
	
	public function __construct(protected NewReleaseSettingService $_service)
	{
		$this->function		= Functions::NEW_RELEASE_SETTING;
		$this->action 		= FormAction::LIST; 
		$this->backRoute 	= 'new_release_setting';
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
			FormAction::CREATE => route('new_release_setting.create.post'),
			FormAction::UPDATE => route('new_release_setting.update.post'),
		};
	}
	
	/* Keep form data
	 * @params: int
	 * @params: string
	 * @params: string
	 * @params: int
	 * @return: void
	 */
	public function keepFormData($id = 0, $brandId = Brand::BAFANG->value, $productIds = [], $name = '', $saleDate = '', $tasteKeyWord = '', $status = TRUE, $updateAt = '')
    {
		/* $formatProductIds = [
			Brand::BAFANG->value => [], 
			Brand::BUYGOOD->value => []
		];
		
		$formatProductIds[$brandId] = $productIds; */
		
		$this->set('formData.id', $id);
		$this->set('formData.brandId', $brandId);
		$this->set('formData.productIds', $productIds);
		$this->set('formData.name', $name);
		$this->set('formData.saleDate', $saleDate);
		$this->set('formData.tasteKeyWord', is_array($tasteKeyWord) ? implode("\r\n", $tasteKeyWord) : $tasteKeyWord);
		$this->set('formData.status', $status);
		$this->set('formData.updateAt', $updateAt);
	}
	
	public function isDataEmpty()
	{
		if (empty(Arr::collapse($this->list)))
			return TRUE;
		else
			return FALSE;
	}
}