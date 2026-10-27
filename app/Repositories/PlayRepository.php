<?php

namespace App\Repositories;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Exception;


class PlayRepository extends Repository
{
	public function __construct()
	{
		
	}
	
	/* Get store menu by tv id
	 * @params: fluent
	 * @return: boolean
	 */
	public function getStoreMenuByTvId($storeId, $tvId)
	{
		try
		{
			$today = now()->format('Y-m-d');
			
			$db = $this->connectTvMenu();
			
			#不取storeId, tvId因Default時不會有
			$result = $db->table('StoreMenu as sm')
						->join('Menus as m', 'm.id', '=', 'sm.menuId')
						->join('MenuDetail as md', 'md.menuId', '=', 'm.id')
						->join('Medias as ma', 'ma.id', '=', 'md.mediaId')
						->where('sm.storeId', $storeId)
						->where('sm.tvId', $tvId)
						->where(function($query) use($today){
							$query->whereNull('ma.startDate')
								->orWhere('ma.startDate', '<=', $today);
						})
						->where(function($query) use($today){
							$query->whereNull('ma.endDate')
								->orWhere('ma.endDate', '>=', $today);
						})
						->where('ma.enabled', TRUE)
						->select('m.menuName', 'm.isDefault')
						->addSelect('md.duration', 'md.sort')
						->addSelect('ma.name as mediaName', 'ma.path', 'ma.type')
						->get()
						->toArray();
		
			return $result;
		}
		catch(Exception $e)
		{
			throw new Exception($e->getMessage());
		}
	}
	
	/* Get store menu by default
	 * @params: fluent
	 * @return: boolean
	 */
	public function getStoreMenuByDefault()
	{
		try
		{
			$today = now()->format('Y-m-d');
			
			$db = $this->connectTvMenu();
			
			#不取storeId, tvId因Default時不會有
			$result = $db->table('Menus as m')
						->join('MenuDetail as md', 'md.menuId', '=', 'm.id')
						->join('Medias as ma', 'ma.id', '=', 'md.mediaId')
						->where('m.isDefault', TRUE)
						->where(function($query) use($today){
							$query->whereNull('ma.startDate')
								->orWhere('ma.startDate', '<=', $today);
						})
						->where(function($query) use($today){
							$query->whereNull('ma.endDate')
								->orWhere('ma.endDate', '>=', $today);
						})
						->where('ma.enabled', TRUE)
						->select('m.menuName', 'm.isDefault')
						->addSelect('md.duration', 'md.sort')
						->addSelect('ma.name as mediaName', 'ma.path', 'ma.type')
						->get()
						->toArray();
		
			return $result;
		}
		catch(Exception $e)
		{
			throw new Exception($e->getMessage());
		}
	}
}
