<?php
#目前未用到
namespace App\Manager;

use App\Models\CurrentUser;
use App\Enums\MenuGroup;
use App\Enums\OpCenter;
use App\Enums\Brand;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;

class AppManager
{
	const SESS_AUTH_USER = 'Sess:AuthUser';
	const SESS_AUTH_MENU = 'Sess:AuthMenu';
	
	public function __construct()
	{
		
	}
	
	/******************** User Basic ********************/
	/* hasAuth
	 * @params: 
	 * @return: boolean
	 */
	public function hasAuth()
	{
		return empty($this->getCurrentUser()) ? FALSE : TRUE;
	}
	
	/* has function Auth(功能權限)
	 * @params: 
	 * @return: boolean
	 */
	public function hasFunctionPermission()
	{
		$currentUser = $this->getCurrentUser();
		
		if ($currentUser->isSupervisor())
			return TRUE;
		
		return empty($currentUser['rolePermission']) ? FALSE : TRUE;
	}
	
	/* has Area Auth(區域權限)
	 * @params: 
	 * @return: boolean
	 */
	public function hasAreaPermission()
	{
		$currentUser = $this->getCurrentUser();
		
		if ($currentUser->isSupervisor())
			return TRUE;
		
		if (empty($currentUser['roleArea']['sales']) && empty($currentUser['roleArea']['purchase']))
			return FALSE;
		else
			return TRUE;
		#return empty($currentUser['roleArea']) ? FALSE : TRUE;
	}
	
	/* 清除登入資訊|Menu
	 * @params: 
	 * @return: boolean
	 */
	public function removeCurrentUser()
	{
		session()->forget(self::SESS_AUTH_USER);
		session()->forget(self::SESS_AUTH_MENU);
		
		return TRUE;
	}
	
	/* 儲存登入資訊(20260504不存AD)
	 * @params: array
	 * @params: array
	 * @return: boolean
	 */
	public function saveCurrentUser($userInfo, $adInfo = [])
	{
		$currentUser = new CurrentUser($userInfo, $adInfo);
		session()->put(self::SESS_AUTH_USER, $currentUser);
		
		return TRUE;
	}
	
	/* Get current user
	 * @params: 
	 * @return: array
	 */
	public function getCurrentUser()
	{
		if (session()->missing(self::SESS_AUTH_USER))
			return FALSE;
		
		return session()->get(self::SESS_AUTH_USER);
	}
	
	/* 更新登入資訊
	 * @params: array
	 * @params: array
	 * @return: boolean
	 */
	public function updateCurrentUserProfile($displayName, $department, $email)
	{
		$currentUser = $this->getCurrentUser();
		$currentUser['displayName'] = $displayName;
		$currentUser['department']	= $department;
		$currentUser['email'] 		= $email;
		
		session()->put(self::SESS_AUTH_USER, $currentUser);
		
		return TRUE;
	}
	
	/* 取已授權的Menu (登入驗後)
	 * @params: 
	 * @return: array
	 */
	public function getAuthMenu()
	{
		$authMenu = [];
		
		#1.若有取過, 直接取Session
		if (env('APP_DEBUG', TRUE) == FALSE && session()->has(self::SESS_AUTH_MENU))
			return session()->get(self::SESS_AUTH_MENU);
		
		#2.取目前登入使用者
		$currentUser = $this->getCurrentUser();
		
		if ($currentUser === FALSE)
			return $authMenu;
		
		#3.取功能選單設定檔
		$menuConfig = config('web.menu');
		
		#4.驗證使用者有權限的選單, 只要驗證到功能即可
		foreach($menuConfig as $key => $groups)
		{
			$menuGroup = MenuGroup::tryFrom($key);
			$keyName = $menuGroup->label();
			$authMenu[$keyName] = [];
			
			foreach($groups as $item)
			{
				if ($currentUser->hasFunctionPermission($item['code']))
				{
					#只取必要欄位
					Arr::forget($item, 'code');
					$item['url'] = route($item['url']);
					$authMenu[$keyName][] = $item;
				}
			}
			
			if (empty($authMenu[$keyName]))
				unset($authMenu[$keyName]);
		}
		
		session()->put(self::SESS_AUTH_MENU, $authMenu);
		
		return $authMenu;
	}
	
	/* 取All Menu:權限設定
	 * @params: 
	 * @return: array
	 */
	public function getMenu()
	{
		$menu = [];
		$menuConfig = config('web.menu');
		
		foreach($menuConfig as $key => $groups)
		{
			$menuGroup = MenuGroup::tryFrom($key);
			$keyName = $menuGroup->label();
			$menu[$keyName] = [];
			
			foreach($groups as $item)
			{
				#只取必要欄位
				Arr::forget($item, ['style', 'url']);
				$menu[$keyName][] = $item;
			}
		}
		
		return $menu;
	}
	/******************** User Basic End ********************/
	
	
	/******************** User Auth Filter ********************/
	#移至各自的manager,因營運中心太複雜
	/* 
	 * @params: 
	 * @return: boolean
	 */
	/* public function getAllowOpCenter($filterOpCenters = [])
	{
		$currentUser = $this->getCurrentUser();
		$authOpCenters = $currentUser->getOpCenterPermissions();
		
		if (empty($filterOpCenters))
			return $authOpCenters;
		else
			return $filterOpCenters;
	} */
	
	/* 
	 * @params: 
	 * @return: boolean
	 */
	/* public function getAllowPurchaseAreas($filterAreas = [])
	{
		$currentUser = $this->getCurrentUser();
		$authAreas = $currentUser->getPurchaseAreaPermissions();
		
		if (empty($filterAreas))
			return $authAreas;
		else
			return array_map('intval', $filterAreas);
	} */
	
	/* 
	 * @params: 
	 * @return: boolean
	 */
	/* public function getAllowSalesAreas($filterAreas = [])
	{
		$currentUser = $this->getCurrentUser();
		$authAreas = $currentUser->getSalesAreaPermissions();
		
		if (empty($filterAreas))
			return $authAreas;
		else
			return array_map('intval', $filterAreas);
	} */
	
	/******************** User Auth Filter End ********************/
}
