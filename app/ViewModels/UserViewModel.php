<?php

namespace App\ViewModels;

use App\Services\UserService;
use App\Facades\AppManager;
use App\Enums\FormAction;
use App\Enums\OpCenter;
use App\Enums\Area;
use App\Enums\RoleGroup;
use App\Enums\Functions;
use App\ViewModels\Attributes\attrStatus;
use App\ViewModels\Attributes\attrActionBar;
use App\ViewModels\Attributes\attrAllowAction;
use Illuminate\Support\Fluent;

class UserViewModel extends Fluent
{
	use attrStatus, attrActionBar, attrAllowAction;
	
	public function __construct(protected UserService $_service)
	{
		$this->function		= Functions::USER;
		$this->action 		= FormAction::LIST; 
		$this->backRoute 	= 'users';
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
		#20260703:區域權限改為POS|訂貨各自獨立
		$this->set('options.functions', AppManager::getMenu());
		#$this->set('options.areas', Area::mapWithKeys());
		$this->set('options.salesAreas', Area::options()); #POS
		$this->set('options.purchaseAreas', Area::options()); #nOrder
		$this->set('options.opCenters', OpCenter::options());
		$this->set('options.supervisorGroupId',RoleGroup::SUPERVISOR->value); 
	}
	
	/* Form submit action
	 * @params: 
	 * @return: string
	 */
	public function getFormAction($formAction) : string
    {
		return match($formAction)
		{
			FormAction::CREATE => route('user.create.post'),
			FormAction::UPDATE => route('user.update.post'),
		};
	}
	
	/* Keep user form data
	 * @params: int
	 * @params: string
	 * @params: string
	 * @params: int
	 * @return: void
	 */
	public function keepFormData($id = 0, $account = '',  $password = '',
						$displayName = '', $department = '', $email = '', $description = '', $isActive = TRUE, 
						$permission = [], $area = [],  
						$updateAt = '', $hasSetPassword = FALSE)
    {
		#info
		$this->set('formData.id', $id);
		$this->set('formData.account', $account);
		$this->set('formData.password', $password);
		$this->set('formData.displayName', $displayName);
		$this->set('formData.department', $department);
		$this->set('formData.email', $email);
		$this->set('formData.description', $description);
		$this->set('formData.isActive', $isActive);
		
		#permission
		$this->set('formData.permission', $permission);
		
		#default全部營運中心
		$this->set('formData.area.opCenter', data_get($area, 'opCenter', []));
		$this->set('formData.area.sales', data_get($area, 'sales', []));
		$this->set('formData.area.purchase', data_get($area, 'purchase', []));
		
		$this->set('formData.updateAt', $updateAt);
		$this->set('formData.hasSetPassword', $hasSetPassword);
	}
	
	
	/* User Data End */
	
	/* 判別列表Role是否可編或可刪
	 * @params: 
	 * @return: boolean
	 */
	public function canUpdateThisUser($thisRoleGroup)
	{
		return ! ($thisRoleGroup == RoleGroup::SUPERVISOR->value);
	}
	
	public function canDeleteThisUser($thisRoleGroup)
	{
		return ! ($thisRoleGroup == RoleGroup::SUPERVISOR->value);
	}
	
	/* Output js */
	/*因與統計不同, 不使用trait response*/
	public function listResponseData()
	{
		$response['status'] 		= $this->status();
		$response['hasResult'] 		= ! empty($this->list);
		
		return $response;
	}
	
	public function listData()
	{
		$response['data'] 				= $this->list;
		$response['supervisorGroupId']	= RoleGroup::SUPERVISOR->value;
		$response['createRoute']	= route('user.create');
		$response['updateRoute']	= route('user.update', ['id' => '_ID']);
		$response['deleteRoute']	= route('user.delete', ['id' => '_ID']);
		
		return $response;
	}
	
	public function detailResponseData()
	{
		$response['status'] 		= $this->status();
		$response['backRoute']		= route($this->backRoute);
		
		return $response;
	}
	
	public function detailData()
	{
		$response = $this->only('formData', 'options');
		$response['formAction'] = $this->getFormAction($this->action);
		$response['actionLabel']= ($this->action == FormAction::CREATE) ? '新增' : '儲存';
		
		return $response;
	}
}