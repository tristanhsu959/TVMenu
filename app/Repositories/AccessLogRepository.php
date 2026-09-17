<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Arr;
use Exception;

class AccessLogRepository extends Repository
{
	
	public function __construct()
	{
		
	}
	
	/* Create Role
	 * @params: string
	 * @params: string
	 * @params: json string
	 * @params: json string
	 * @return: boolean
	 */
	public function insert($userId, $userAccount)
	{
		$db = $this->connectSalesDashboard('access_log');
		
		$db->updateOrInsert(
			['userId' => $userId, 'userAccount' => $userAccount],
			['updateAt' => now()->format('Y-m-d H:i:s')]
		);
		
		return TRUE;
	}
}
