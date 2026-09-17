<?php

namespace App\ViewModels\Attributes;

use App\Facades\AppManager;
use App\Models\CurrentUser;
use App\Traits\AuthTrait;
use App\Enums\Operation;
use App\Enums\Area;
use App\Enums\Brand;

#Status & Message
trait attrAllowAction
{
	public function isCurrentUser($userId)
	{
		$currentUser = AppManager::getCurrentUser();
		
		return ($currentUser->id == $userId);
	}
	
	/* Action permission
	 * @params: string
	 * @return: void
	 */
	public function hasPermission()
	{
		#改為只有判別function
		#Middleware已有過濾, 可不用
		$currentUser = AppManager::getCurrentUser();
		return $currentUser->hasPermissionTo($this->function->value);
	}
	
	/* Area permission
	 * @params: string
	 * @return: void
	 */
	public function getPurchaseOpCenterOptions()
	{
		$currentUser 	= AppManager::getCurrentUser();
		$opCenters 		= $currentUser->getOpCenterPermissionMap();
		
		if ($this->brand != Brand::BAFANG) #八方才有
			return [];
		
		#只有單個, 就無需顯示
		if (count($opCenters) <= 1)
			return [];
		
		return $opCenters;
	}
	
	/* Area permission
	 * @params: string
	 * @return: void
	 */
	public function getAllAreaOptions()
	{
		return Area::options();
	}
	
	/* Area permission
	 * @params: string
	 * @return: void
	 */
	public function getPurchaseAreaOptions()
	{
		$currentUser	= AppManager::getCurrentUser();
		$areas 			= $currentUser->getPurchaseAreaPermissionMap();
		
		#單區授權不顯示(芳珍無訂貨功能)
		if (count($areas) <= 1)
			return [];
		
		return $areas;
	}
	
	/* Area permission
	 * @params: string
	 * @return: void
	 */
	public function getSalesAreaOptions()
	{
		$currentUser 	= AppManager::getCurrentUser();
		$areas 			= $currentUser->getSalesAreaPermissionMap();
		
		if ($this->brand == Brand::FJVEGGIE) #芳珍不顯示
			return [];
		
		#單區授權不顯示, return [], 後續會去取user aurh areas
		if (count($areas) <= 1)
			return [];
		
		return $areas;
	}
	
	/* Action permission
	 * @params: string
	 * @return: void
	 */
	/* public function canQuery()
	{
		$currentUser = AppManager::getCurrentUser();
		return $currentUser->hasActionPermission($this->function->value, Operation::READ->value);
	}
	
	public function canCreate()
	{
		$currentUser = AppManager::getCurrentUser();
		return $currentUser->hasActionPermission($this->function->value, Operation::CREATE->value);
	}
	
	public function canUpdate()
	{
		$currentUser = AppManager::getCurrentUser();
		return $currentUser->hasActionPermission($this->function->value, Operation::UPDATE->value);
	}
	
	public function canDelete()
	{
		$currentUser = AppManager::getCurrentUser();
		return $currentUser->hasActionPermission($this->function->value, Operation::DELETE->value);
	} */
}