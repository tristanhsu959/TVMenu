<?php

namespace App\Repositories;

use App\Enums\RoleGroup;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Arr;
use Exception;

class UserRepository extends Repository
{
	
	public function __construct()
	{
		
	}
	
	/* Get user list by query conditions
	 * @params: string
	 * @params: string
	 * @params: int
	 * @return: array
	 */
	public function getList()
	{
		$db = $this->connectSalesDashboard('user as a');
			
		$result = $db->select('a.userId', 'a.userAccount', 'a.userPassword', 'a.userDisplayName', 'a.department')
			->addSelect('a.email', 'b.roleGroup', 'a.isActive', 'a.updateAt', 'l.updateAt as accessTime')
			->leftJoin('role as b', 'b.roleUserId', '=', 'a.userId')
			->leftJoin('access_log as l', 'l.userId', '=', 'a.userId')
			->get()
			->toArray();
		
		$result = Arr::map($result, function ($item, string $key) {
			$item['rolePermission']	= empty($item['rolePermission']) ? [] : json_decode($item['rolePermission'], TRUE);
			$item['roleArea'] 		= empty($item['roleArea']) ? [] : json_decode($item['roleArea'], TRUE);
			return $item;
		});
			
		return $result;
	}
	
	/* Get user by account
	 * @params: string
	 * @return: array
	 */
	public function getIdByAccount($account, $exceptId)
	{
		$db = $this->connectSalesDashboard('user');
			
		$result = $db->select('userId')
					->where('userAccount', '=', $account)
					->when($exceptId, function($query, $exceptId){
						return $query->where('userId', '!=', $exceptId);
					})
					->get()
					->first();
		
		return data_get($result, 'userId', 0);
	}
	
	/* Create Account
	 * @params: string
	 * @params: string
	 * @params: int
	 * @return: boolean
	 */
	public function insert($account, $password, $displayName, $department, $email, $description, $isActive, $roleGroupId, $permission, $area)
	{
		$db = $this->connectSalesDashboard();
		$db->beginTransaction();
		
		try 
		{
			$insertId = $this->_insertUser($account, $password, $displayName, $department, $email, $isActive);
			
			$this->_insertRole($insertId, $roleGroupId, $permission, $area, $description);
			
			$db->commit();

			return TRUE;
		} 
		catch (Exception $e) 
		{
			$db->rollBack();
			throw new Exception($e->getMessage());
		}
		
		return TRUE;
	}
	
	/* Create user
	 * @params: string
	 * @params: string
	 * @params: string
	 * @params: string
	 * @params: boolean
	 * @return: boolean
	 */
	private function _insertUser($account, $password, $displayName, $department, $email, $isActive)
	{
		$data['userAccount']	= $account;
		$data['userPassword'] 	= $password;
		$data['userDisplayName']= $displayName;
		$data['department']		= $department;
		$data['email']			= $email;
		$data['isActive']		= $isActive;
		$data['createAt'] 		= now()->format('Y-m-d H:i:s');
		$data['updateAt'] 		= $data['createAt'];
		
		$db = $this->connectSalesDashboard();
		$insertId = $db->table('user')
			->insertGetId($data);
		
		return $insertId;
	}
	
	/* Create user
	 * @params: string
	 * @params: string
	 * @params: string
	 * @params: string
	 * @params: boolean
	 * @return: boolean
	 */
	private function _insertRole($userId, $roleGroupId, $permission, $area, $description)
	{
		$data['roleUserId']		= $userId;
		$data['roleGroup'] 		= $roleGroupId;
		$data['rolePermission']	= json_encode($permission);
		$data['roleArea']		= json_encode($area);
		$data['description'] 	= $description;
		
		$db = $this->connectSalesDashboard();
		$insertId = $db->table('role')
			->insert($data);
		
		return TRUE;
	}
	
	/* Get user by id
	 * @params: int
	 * @return: array
	 */
	public function getById($id)
	{
		$db = $this->connectSalesDashboard('user as a');
		
		$result = $db->select('a.userId', 'a.userAccount', 'a.userPassword', 'a.userDisplayName', 'a.department')
				->addSelect('a.email', 'a.isActive', 'a.updateAt')
				->addSelect('b.roleGroup', 'b.rolePermission', 'b.roleArea', 'b.description')
				->leftJoin('role as b', 'b.roleUserId', '=', 'a.userId')
				->where('userId', '=', $id)
				->first();
		
		$result['rolePermission']	= empty($result['rolePermission']) ? [] : json_decode($result['rolePermission'], TRUE);
		$result['roleArea'] 		= empty($result['roleArea']) ? [] : json_decode($result['roleArea'], TRUE);
	
		return $result;
	}
	
	/* Update user data by id
	 * @params: int
	 * @params: string
	 * @params: string
	 * @params: int
	 * @return: boolean
	 */
	public function update($id, $account, $password, $displayName, $department, $email, $description, $isActive, $permission, $area)
	{
		$db = $this->connectSalesDashboard();
		$db->beginTransaction();
		
		try 
		{
			$this->_updateUser($id, $account, $password, $displayName, $department, $email, $isActive);
			
			$this->_updateRole($id, $permission, $area, $description);
			
			$db->commit();

			return TRUE;
		} 
		catch (Exception $e) 
		{
			$db->rollBack();
			throw new Exception($e->getMessage());
		}
		
		return TRUE;
	}
	
	/* Create user
	 * @params: int
	 * @params: string
	 * @params: string
	 * @params: string
	 * @params: string
	 * @params: boolean
	 * @return: boolean
	 */
	private function _updateUser($id, $account, $password, $displayName, $department, $email, $isActive)
	{
		$data['userAccount']	= $account;
		
		if (! empty($password))
			$data['userPassword'] 	= $password;
		
		$data['userDisplayName']= $displayName;
		$data['department']		= $department;
		$data['email']			= $email;
		$data['isActive']		= $isActive;
		$data['updateAt'] 		= now()->format('Y-m-d H:i:s');
		
		$db = $this->connectSalesDashboard();
		
		$db->table('user')
			->where('userId', '=', $id)
			->update($data);
		
		return TRUE;
	}
	
	/* Create user
	 * @params: string
	 * @params: string
	 * @params: string
	 * @params: string
	 * @params: boolean
	 * @return: boolean
	 */
	private function _updateRole($userId, $permission, $area, $description)
	{
		$data['rolePermission']	= json_encode($permission);
		$data['roleArea']		= json_encode($area);
		$data['description']	= $description;
		
		$db = $this->connectSalesDashboard();
		
		$db->table('role')
			 ->where('roleUserId', '=', $userId)
			 ->update($data);
		
		return TRUE;
	}
	
	/* Remove user by id
	 * @params: int
	 * @return: boolean
	 */
	public function remove($userId)
	{
		$db = $this->connectSalesDashboard();
		$db->beginTransaction();
		
		try 
		{
			$db->table('user')
				->where('userId', '=', $userId)
				->delete();
			
			$db->table('role')
				->where('roleUserId', '=', $userId)
				->delete();
				
			$db->commit();

			return TRUE;
		} 
		catch (Exception $e) 
		{
			$db->rollBack();
			throw new Exception($e->getMessage());
		}
		
		return TRUE;
	}
	
	/* Create user
	 * @params: int
	 * @params: string
	 * @params: string
	 * @params: string
	 * @params: string
	 * @return: boolean
	 */
	public function updateProfile($id, $password, $displayName, $department, $email)
	{
		if (! empty($password))
			$data['userPassword'] 	= $password;
		
		if (! empty($displayName))
			$data['userDisplayName']= $displayName;
		
		if (! empty($department))
			$data['department']	= $department;
		
		if (! empty($email))
			$data['email'] = $email;
		
		$data['updateAt'] = now()->format('Y-m-d H:i:s');
		
		$db = $this->connectSalesDashboard();
		
		$db->table('user')
			->where('userId', '=', $id)
			->update($data);
		
		return TRUE;
	}
}
