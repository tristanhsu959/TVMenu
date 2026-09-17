<?php

namespace App\ViewModels;

use App\Enums\FormAction;
use App\Enums\Functions;
use App\Enums\Brand;
use App\ViewModels\Attributes\attrStatus;
use App\ViewModels\Attributes\attrActionBar;
use App\ViewModels\Attributes\attrAllowAction;
use Illuminate\Support\Arr;
use Illuminate\Support\Fluent;

class ProductViewModel extends Fluent
{
	use attrStatus, attrActionBar, attrAllowAction;
	
	public function __construct()
	{
		$this->function		= Functions::PRODUCT;
		$this->action 		= FormAction::LIST; 
		$this->backRoute 	= 'products';
		$this->success();
		$this->list			= [];
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
		$this->set('options.categories', config('web.sales.category'));
	}
	
	/* Form submit action
	 * @params: 
	 * @return: string
	 */
	public function getFormAction($formAction) : string
    {
		return match($formAction)
		{
			FormAction::CREATE => route('product.create.post'),
			FormAction::UPDATE => route('product.update.post'),
		};
	}
	
	/* Keep form data
	 * @params: int
	 * @params: string
	 * @params: string
	 * @params: int
	 * @return: void
	 */
	public function keepFormData($id = 0, $brandId = 0, $category = 0, $name = '', $primaryNo = '', $secondaryNo = '')
    {
		$primaryNo	= is_array($primaryNo) ? Arr::join($primaryNo, "\r\n") : $primaryNo;
		$secondaryNo= is_array($secondaryNo) ? Arr::join($secondaryNo, "\r\n") : $secondaryNo;
		
		$this->set('formData.id', $id);
		$this->set('formData.brandId', $brandId);
		$this->set('formData.category', $category);
		$this->set('formData.name', $name);
		$this->set('formData.primaryNo', $primaryNo);
		$this->set('formData.secondaryNo', $secondaryNo);
		$this->set('formData.buygoodId', Brand::BUYGOOD->value); #給js用
	}
	
	/* Output js */
	/*與統計不同,不使用trait*/
	public function responseData()
	{
		$response['status'] 	= $this->status();
		$response['hasResult'] 	= !empty(Arr::collapse($this->list));
		$response['createFormAction'] = route('product.create');
		$response['options'] 	= $this->options;
		
		return $response;
	}
	
	public function responseList()
	{
		return $this->only('list', 'options');
	}
	
	public function responseDetail()
	{
		$response = $this->only('formData', 'options');
		$response['formData']['formAction'] = $this->getFormAction($this->action);
		
		return $response;
	}
}