<?php

namespace App\ViewModels;

use App\Enums\FormAction;
use App\ViewModels\Attributes\attrStatus;
use Illuminate\Support\Fluent;

class AuthViewModel extends Fluent
{
	use attrStatus;
	
	public function __construct()
	{
		#Default data
		$this->action = FormAction::SIGNIN;
		$this->keepFormData();
		$this->success();
	}
	
	/* Keep signin form data : account only, 以防會使用到
	 * @params: string
	 * @return: void
	 */
	public function keepFormData($account = '', $password = '')
    {
		$this->set('formData.account', $account);
		$this->set('formData.password', $password);
	}
	
	/* Output json
	 * @params: string
	 * @return: void
	 */
	public function responseData()
    {
		$this->set('formData.formAction', route('signin.post'));
		$this->set('formData.forgetPasswordAction', route('forgetPassword.send'));
		$this->set('formData.oethRedirect', route('oeth.redirect'));
		
		return $this->only('formData');
	}
	
	/* Output json
	 * @params: string
	 * @return: void
	 */
	/* public function changePasswordData()
    {
		$formData['formAction']	= route('forgetPassword.send');
		$formData['account']	= '';
		
		return $formData;
	} */
}