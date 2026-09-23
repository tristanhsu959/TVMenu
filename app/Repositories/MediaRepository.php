<?php

namespace App\Repositories;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Exception;


class MediaRepository extends Repository
{
	public function __construct()
	{
		
	}
	
	/* Get media list
	 * @params: string
	 * @params: string
	 * @params: string
	 * @return: array
	 */
	public function getList($request = NULL)
	{
		$db = $this->connectTvMenu();
		
		$result = $db
			->table('Medias')
			->select('_id', 'name', 'startDate', 'endDate', 'path', 'type', 'enabled')
			/* ->when(empty($request), function ($query) use ($excepts) {
					$query->whereNotIn('o.posid', $excepts);
			}) */
			->get()
			->toArray();
		
		return $result;
	}
	
	/* Create media
	 * @params: fluent
	 * @return: boolean
	 */
	public function insert($request)
	{
		try
		{
			$data['name']		= $request->mediaName;
			$data['path'] 		= $request->path;
			$data['startDate']	= $request->stDate;
			$data['endDate']	= $request->endDate;
			$data['type']		= $request->type;
			$data['enabled']	= $request->enabled;
			
			$db = $this->connectTvMenu();
			
			$insertId = $db->table('Medias')
						->insertGetId($data);
		
			return $insertId;
		}
		catch(Exception $e)
		{
			throw new Exception('媒體庫新增資料失敗');
		}
	}
	
	/* Remove media
	 * @params: fluent
	 * @return: boolean
	 */
	public function remove($id)
	{
		try
		{
			$db = $this->connectTvMenu();
			
			$db->table('Medias')
				->where('_id', '=', $id)
				->delete();
		
			return TRUE;
		}
		catch(Exception $e)
		{
			throw new Exception('媒體庫刪除資料失敗');
		}
	}
}
