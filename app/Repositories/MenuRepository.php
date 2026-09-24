<?php

namespace App\Repositories;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Exception;


class MenuRepository extends Repository
{
	public function __construct()
	{
		
	}
	
	/* Reset all menu default value
	 * @params: fluent
	 * @return: boolean
	 */
	public function resetDefault()
	{
		try
		{
			$data['isDefault'] = FALSE;
			
			$db = $this->connectTvMenu();
			$db->table('Menus')->update($data);
		
			return TRUE;
		}
		catch(Exception $e)
		{
			throw new Exception('重置預設Menu失敗');
		}
	}
	
	/* Get menu list
	 * @params: fluent
	 * @return: array
	 */
	public function getList($request = NULL)
	{
		$db = $this->connectTvMenu();
		
		$result = $db
			->table('Menus')
			->select('_id', 'menuName', 'isDefault')
			/* ->when(empty($request), function ($query) use ($excepts) {
					$query->whereNotIn('o.posid', $excepts);
			}) */
			->get()
			->toArray();
		
		return $result;
	}
	
	/* Create menu
	 * @params: fluent
	 * @return: boolean
	 */
	public function insert($request)
	{
		$db = $this->connectTvMenu();
		$db->beginTransaction();
		
		try 
		{
			$insertId = $this->_insertMenu($db, $request->menuName, $request->isDefault);
			
			$this->_insertDetail($db, $insertId, $request->medias);
			
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
	
	/* Create menu
	 * @params: fluent
	 * @return: boolean
	 */
	public function _insertMenu($db, $menuName, $isDefault)
	{
		$data['menuName']	= $menuName;
		$data['isDefault'] 	= boolval($isDefault);
			
		$insertId = $db->table('Menus')
						->insertGetId($data);
		
		return $insertId;
	}
	
	/* Create menu details
	 * @params: fluent
	 * @return: boolean
	 */
	public function _insertDetail($db, $menuId, $medias)
	{
		$data = [];
		
		foreach($medias as $media)
		{
			$row['menuId']		= $menuId;
			$row['mediaId'] 	= $media['id'];
			$row['duration'] 	= empty($media['duration']) ? 5 : intval($media['duration']);
			$row['sort'] 		= intval($media['sort']);
			
			$data[] = $row;
		}
			
		$db->table('MenuDetail')->insert($data);
		
		return TRUE;
	}
	
	/* Get menu by id
	 * @params: fluent
	 * @return: array
	 */
	public function getById($id)
	{
		$db = $this->connectTvMenu();
		
		$result = $db
			->table('Menus as m')
			->leftJoin('MenuDetail as d', 'd.menuId', '=', 'm._id')
			->select('m._id', 'm.menuName', 'm.isDefault')
			->addSelect('d.mediaId', 'd.duration', 'd.sort')
			->where('m._id', '=', $id)
			->get()
			->toArray();
		
		return $result;
	}
	
	/* Update menu
	 * @params: fluent
	 * @return: boolean
	 */
	public function update($request)
	{
		$db = $this->connectTvMenu();
		$db->beginTransaction();
		
		try 
		{
			$this->_updateMenu($db, $request->id, $request->menuName, $request->isDefault);
			
			$this->_removeDetailByMenuId($db, $request->id);
			
			$this->_insertDetail($db, $request->id, $request->medias);
			
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
	
	/* Update menu
	 * @params: fluent
	 * @return: boolean
	 */
	public function _updateMenu($db, $id, $menuName, $isDefault)
	{
		$data['menuName']	= $menuName;
		$data['isDefault'] 	= $isDefault;
			
		$db->table('Menus')
			->where('_id', '=', $id)
			->update($data);
		
		return TRUE;
	}
	
	/* Update menu details
	 * @params: fluent
	 * @return: boolean
	 */
	public function _removeDetailByMenuId($db, $menuId)
	{
		$db->table('MenuDetail')
			->where('menuId', '=', $menuId)
			->delete();
		
		return TRUE;
	}
	
	/* Remove media
	 * @params: fluent
	 * @return: boolean
	 */
	public function remove($id)
	{
		$db = $this->connectTvMenu();
		$db->beginTransaction();
		
		try 
		{
			$db->table('Menus')
				->where('_id', '=', $id)
				->delete();
			
			$db->table('MenuDetail')
				->where('menuId', '=', $id)
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
}
