<?php

namespace App\Services;

use App\Facades\AppManager;
use App\Repositories\UserRepository;
use App\Libraries\ResponseLib;
use App\Enums\RoleGroup;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Exception;
use Log;

class UserService
{
	public function __construct(protected UserRepository $_repository)
	{
	}
	
	/* 取帳號清單(Get ALL)
	 * @params: 
	 * @return: array
	 */
	public function getList()
	{
		try
		{
			$list = $this->_repository->getList();
			
			$list = collect($list)->map(function($item, $key){
				$item['hasSysPassword'] = empty($item['userPassword']) ? FALSE : TRUE;
				unset($item['userPassword']);
				
				return $item;
			})->toArray();
			
			return ResponseLib::initialize($list)->success();
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			return ResponseLib::initialize()->fail('讀取帳號清單時發生錯誤');
		}
	}
	
	/* Create Account
	 * @params: string
	 * @params: string
	 * @params: int
	 * @return: array
	 */
	public function createUser($account, $password, $displayName, $department, $email, $description, $isActive, 
								$permission, $areaPermission)
	{
		try
		{
			#1.Check account
			if ($this->_isAccountExist($account) === TRUE)
				throw new Exception('此帳號已存在');
			
			#2.Hash password
			$password = Hash::make($password);
			
			#3. Convert area permission
			$areaPermission['opCenter'] = data_get($areaPermission, 'opCenter', []);
			$areaPermission['sales'] 	= array_map('intval', data_get($areaPermission, 'sales', []));
			$areaPermission['purchase'] = array_map('intval', data_get($areaPermission, 'purchase', []));
			
			#4. Create user
			$this->_repository->insert($account, $password, $displayName, $department, $email, $description, $isActive, 
										RoleGroup::USER->value, $permission, $areaPermission);
		
			return ResponseLib::initialize()->success();
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			return ResponseLib::initialize()->fail($e->getMessage());
		}
	}
	
	/* 驗證帳號
	 * @params: string
	 * @params: int
	 * @return: array
	 */
	private function _isAccountExist($account, $exceptId = FALSE)
	{
		try
		{
			$id = $this->_repository->getIdByAccount($account, $exceptId); 
			
			return empty($id) ? FALSE : TRUE;
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('驗證帳號發生錯誤');
		}
	}
	
	/* Update:取User Data
	 * @params: int
	 * @return: array
	 */
	public function getUserById($id)
	{
		try
		{
			$result = $this->_repository->getById($id);
			
			return ResponseLib::initialize($result)->success();
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			return ResponseLib::initialize()->fail('讀取帳號資料發生錯誤');
		}
	}
	
	/* Update User
	 * @params: int
	 * @params: string
	 * @params: string
	 * @params: int
	 * @return: array
	 */
	public function updateUser($id, $account, $password, $displayName, $department, $email, $description, $isActive, 
								$permission, $areaPermission)
	{
		try
		{
			#1.Check account
			if ($this->_isAccountExist($account, $id) === TRUE)
				throw new Exception('此帳號已存在');
			
			#2.Hash password
			if (! empty($password))
				$password = Hash::make($password);
			
			#3. Convert area permission
			$areaPermission['opCenter'] = data_get($areaPermission, 'opCenter', []);
			$areaPermission['sales'] 	= array_map('intval', data_get($areaPermission, 'sales', []));
			$areaPermission['purchase'] = array_map('intval', data_get($areaPermission, 'purchase', []));
			
			#4. Update user
			$this->_repository->update($id, $account, $password, $displayName, $department, $email, $description, $isActive, 
										$permission, $areaPermission);
			
			return ResponseLib::initialize()->success();
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			return ResponseLib::initialize()->fail($e->getMessage());
		}
	}
	
	/* Remove User
	 * @params: int
	 * @return: array
	 */
	public function deleteUser($id)
	{
		try
		{
			$this->_repository->remove($id);
			return ResponseLib::initialize()->success();
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			return ResponseLib::initialize()->fail('刪除帳號失敗');
		}
	}
	
	/* User profile update
	 * @params: int 
	 * @params: string
	 * @params: string
	 * @params: string
	 * @params: string
	 * @return: array
	 */
	public function updateProfile($id, $password, $displayName, $department, $email)
	{
		try
		{
			$password = empty($password) ? $password : Hash::make($password);
			$this->_repository->updateProfile($id, $password, $displayName, $department, $email);
			
			#更新同步
			$user = $this->_repository->getById($id);
			AppManager::updateCurrentUserProfile($user['userDisplayName'], $user['department'], $user['email']);
			
			return ResponseLib::initialize()->success();
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			return ResponseLib::initialize()->fail($e->getMessage());
		}
	}
	
}
