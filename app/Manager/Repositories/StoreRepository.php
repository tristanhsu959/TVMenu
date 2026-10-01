<?php

namespace App\Manager\Repositories;

use App\Repositories\Repository;
use App\Enums\Brand;
use Illuminate\Support\Facades\DB;

/* ezOrder DB */
class StoreRepository extends Repository
{
	
	/* 取門店清單
	 * @params: array
	 * @return: array
	 */
	public function getStoreList($brand, $checkDay)
	{
		$db = $this->connectQuickOrder();
		
		#Area非都有值
		$result = $db
			->table('Stores as s')
			#->join('StoreSection as st', 'st._Id', '=', 's.section')
			->select('s.brand', 's.storeId', 's.storeName', 's.closeDate', 's.posid as posId')
			#->addSelect('st.name as areaName')
			->when(! empty($brand), function ($query) use($brand){
				$query->where('s.brand', '=', $brand);
			})
			->where(function ($query) use($checkDay) {
                $query->whereNull('s.closeDate')
						->orWhere('s.closeDate', '>=', $checkDay);
            })
			->get()
			->toArray(); 
		
		return $result;
	}
	
	/* 取門店By Id
	 * @params: array
	 * @return: array
	 */
	public function getStoreById($storeId)
	{
		$db = $this->connectQuickOrder();
		
		#Area非都有值
		$result = $db
			->table('Stores')
			->select('brand', 'storeId', 'storeName')
			->first(); 
		
		return $result;
	}
}