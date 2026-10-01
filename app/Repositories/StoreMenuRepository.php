<?php

namespace App\Repositories;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Exception;


class StoreMenuRepository extends Repository
{
	public function __construct()
	{
		
	}
	
	/* Reset all menu default value
	 * @params: fluent
	 * @return: boolean
	 */
	public function getTvCount()
	{
		try
		{
			$db = $this->connectTvMenu();
			
			#把storeId改為對應_id
			$result = $db->table('StoreMenu')
						->select('storeId')
						->selectRaw('count(tvId) as tvCount')
						->groupBy('storeId')
						->get()
						->toArray();
		
			return $result;
		}
		catch(Exception $e)
		{
			throw new Exception('讀取TV數量發生錯誤');
		}
	}
	
	/* Get menu by id
	 * @params: fluent
	 * @return: array
	 */
	public function getById($storeId)
	{
		#for testing
		$test = json_decode('[{
			"tvId": 1,
			"menuId": 4
		},
        {
			"tvId": 2,
			"menuId": 7
		}
		]', true);
		
		return $test;
		
		
		$db = $this->connectTvMenu();
		
		$result = $db
			->table('StoreMenu')
			->select('storeId', 'tvId', 'menuId')
			->where('storeId', '=', $storeId)
			->orderBy('tvId')
			->get()
			->toArray();
		
		return $result;
	}
	
	
	/* Insert store menu
	 * @params: fluent
	 * @return: boolean
	 */
	public function upsert($storeId, $menus)
	{
		$db = $this->connectTvMenu();
		$db->beginTransaction();
		
		try 
		{
			$this->_removeById($db, $storeId);
			
			$this->_insertMenu($db, $storeId, $menus);
			
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
	
	/* Update menu details
	 * @params: fluent
	 * @return: boolean
	 */
	public function _removeById($db, $storeId)
	{
		$db->table('StoreMenu')
			->where('storeId', '=', $storeId)
			->delete();
		
		return TRUE;
	}
	
	/* Create menu
	 * @params: fluent
	 * @return: boolean
	 */
	public function _insertMenu($db, $storeId, $menus)
	{
		$data = [];
		
		foreach($menus as $menu)
		{
			$row['storeId']		= $storeId;
			$row['tvId'] 		= $menu['tvId'];
			$row['menuId'] 		= $menu['menuId'];
			
			$data[] = $row;
		}
			
		$db->table('StoreMenu')->insert($data);
		
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
