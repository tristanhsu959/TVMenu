<?php

namespace App\Repositories;

use App\Repositories\Repository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Exception;
use Log;

class ForgetPasswordRepository extends Repository
{
	public function __construct()
	{
		
	}
	
	/* Get user mail by account
	 * @params: string
	 * @return: array
	 */
	public function getUserByAccount($account)
	{
		try
		{
			$db = $this->connectSalesDashboard('user');
				
			$result = $db
					->select('userId', 'userAccount', 'email', 'isActive')
					->where('userAccount', '=', $account)
					->get()
					->first();
			
			return $result;
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			return FALSE;
		}
	}
	
	/* Get user mail by account
	 * @params: string
	 * @return: array
	 */
	public function insert($token, $userId, $expiredMins)
	{
		try
		{
			$data['token']		= $token;
			$data['userId'] 	= $userId;
			$data['createAt'] 	= now()->format('Y-m-d H:i:s');
			$data['expiredAt'] 	= now()->addMinutes(10)->format('Y-m-d H:i:s');
			
			$db = $this->connectSalesDashboard('forget_password');
			$result = $db->insert($data);
			
			return $result;
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			return FALSE;
		}
	}
	
	/* Get user mail by account
	 * @params: string
	 * @return: array
	 */
	public function getInfoByToken($token)
	{
		try
		{
			$db = $this->connectSalesDashboard();
			$result = $db->table('forget_password as f')
						->join('user as u', 'u.userId', '=', 'f.userId')
						->where('f.token', '=', $token)
						->select('u.userId', 'u.userAccount', 'u.userDisplayName')
						->addSelect('f.expiredAt')
						->get()->first();
			
			return $result;
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			return FALSE;
		}
	}
	
	/* Get user mail by account
	 * @params: string
	 * @return: array
	 */
	public function updatePasswordByUserId($userId, $password)
	{
		try
		{
			$data['userPassword']	= $password;
			$data['updateAt'] 		= now()->format('Y-m-d H:i:s');
			
			$db = $this->connectSalesDashboard();
			$db->table('user')
				->where('userId', '=', $userId)
				->update($data);
			
			return TRUE;
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			return FALSE;
		}
	}
}
