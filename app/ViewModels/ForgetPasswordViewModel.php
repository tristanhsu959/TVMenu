<?php

namespace App\ViewModels;

use App\Enums\FormAction;
use App\ViewModels\Attributes\attrStatus;
use Illuminate\Support\Fluent;

class forgetPasswordViewModel extends Fluent
{
	use attrStatus;
	
	public function __construct()
	{
		#給設定password form使用, 非login頁面的send link
		$this->function	= NULL; #獨立頁面可不設定
		$this->action 	= FormAction::UPDATE; #不影響
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
	}
	
	/* Keep signin form data : account only, 以防會使用到
	 * @params: string
	 * @return: void
	 */
	public function keepFormData($id = 0, $account = '', $name = '')
    {
		#不存Password
		$this->set('formData.id', $id);
		$this->set('formData.account', $account);
		$this->set('formData.name', $name);
	}
	
	/* Output json
	 * @params: string
	 * @return: void
	 */
	public function responseData()
    {
		$this->set('formData.formAction', route('forgetPassword.setting.post'));
		return $this->only('formData');
	}
}