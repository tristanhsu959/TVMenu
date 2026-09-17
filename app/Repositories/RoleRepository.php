<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Arr;
use Exception;

class RoleRepository extends Repository
{
	
	public function __construct()
	{
		
	}
	/* Case-sensitive in ubuntu */
	/* Get role list from DB 
	 * @params: 
	 * @return: array
	 */
	public function getList()
	{
		$db = $this->connectSalesDashboard('role');
			
		$result = $db
			->select('roleId', 'roleName', 'roleGroup', 'roleArea', 'updateAt')
			->orderBy('roleName')
			->get()
			->toArray();
		
		#處理Json type
		foreach($result as $key => $item)
		{
			$result[$key] = Arr::map($item, function ($value, string $key) {
				if ($key == 'roleArea')
					return empty($value) ? [] : json_decode($value, TRUE);
				else
					return $value;
			});
		}
			
		return $result;
	}
	
	/* Create Role
	 * @params: string
	 * @params: string
	 * @params: json string
	 * @params: json string
	 * @return: boolean
	 */
	public function insert($name, $group, $permission, $area)
	{
		$roleData['roleName']		= $name;
		$roleData['roleGroup'] 		= $group;
		$roleData['rolePermission'] = json_encode($permission);
		$roleData['roleArea'] 		= json_encode($area);
		$roleData['createAt'] 		= now()->format('Y-m-d H:i:s');
		$roleData['updateAt'] 		= $roleData['createAt'];
		
		$db = $this->connectSalesDashboard('role');
		$id = $db->insertGetId($roleData);
		
		return TRUE;
	}
	
	/* Get Role Data
	 * @params: int
	 * @return: array
	 */
	public function getById($id)
	{
		$db = $this->connectSalesDashboard('role');
			
		$result = $db->select('roleId', 'roleName', 'roleGroup', 'rolePermission', 'roleArea', 'updateAt')
					->where('roleId', '=', $id)
					->get()->first();
		
		
		$result['rolePermission'] 	= empty($result['rolePermission']) ? [] : json_decode($result['rolePermission'], TRUE);
		$result['roleArea'] 		= empty($result['roleArea']) ? [] : json_decode($result['roleArea'], TRUE);
			
		return $result;
	}
	
	/* Update Role
	 * @params: int
	 * @params: string
	 * @params: int
	 * @params: json string
	 * @params: json string
	 * @return: boolean
	 */
	public function update($id, $name, $group, $permission, $area)
	{
		#只能用facade
		$roleData['roleName']		= $name;
		$roleData['roleGroup'] 		= $group;
		$roleData['rolePermission']	= json_encode($permission);
		$roleData['roleArea'] 		= json_encode($area);
		$roleData['updateAt'] 		= now()->format('Y-m-d H:i:s');
		
		$db = $this->connectSalesDashboard('role');
		$db->where('roleId', '=', $id)->update($roleData);
		
		return TRUE;
	}
	
	/* Remove Role
	 * @params: int
	 * @return: boolean
	 */
	public function remove($roleId)
	{
		$db = $this->connectSalesDashboard('role');
		$db->where('roleId', '=', $roleId)->delete();
		
		return TRUE;
	}
}
