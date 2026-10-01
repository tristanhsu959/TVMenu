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
	 * @params: fluent
	 * @return: array
	 */
	public function getList($enabled = FALSE)
	{
		$db = $this->connectTvMenu();
		
		$result = $db
			->table('Medias')
			->select('id', 'name', 'startDate', 'endDate', 'path', 'type', 'enabled')
			->when($enabled, function ($query) use ($enabled) {
				$query->where('enabled', $enabled);
			})
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
		$data['name']		= $request->mediaName;
		$data['path'] 		= $request->path;
		$data['startDate']	= $request->stDate;
		$data['endDate']	= $request->endDate;
		$data['type']		= $request->type;
		$data['enabled']	= $request->enabled;
		
		$db = $this->connectTvMenu();
		
		$insertId = $db->table('Medias')->insertGetId($data);
		
		return $insertId;
	}
	
	/* Get media by id
	 * @params: fluent
	 * @return: array
	 */
	public function getById($id)
	{
		$db = $this->connectTvMenu();
		
		$result = $db
			->table('Medias')
			->select('id', 'name', 'startDate', 'endDate', 'path', 'type', 'enabled')
			->where('id', '=', $id)
			->get()
			->first();
		
		return $result;
	}
	
	/* Update media
	 * @params: fluent
	 * @return: boolean
	 */
	public function update($request)
	{
		if (! empty($request->mediaName))
			$data['name']		= $request->mediaName;
		
		$data['startDate']	= $request->stDate;
		$data['endDate']	= $request->endDate;
		$data['enabled']	= $request->enabled;
		
		$db = $this->connectTvMenu();
		
		$db->table('Medias')
				->where('id', '=', $request->id)
				->update($data);
		
		return TRUE;		
	}
	
	/* Remove media
	 * @params: fluent
	 * @return: boolean
	 */
	public function remove($id)
	{
		$db = $this->connectTvMenu();
			
		$db->table('Medias')
			->where('id', '=', $id)
			->delete();
		
		return TRUE;
	}
}
