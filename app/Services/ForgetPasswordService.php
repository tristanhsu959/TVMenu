<?php

namespace App\Services;

use App\Facades\AppManager;
use App\Repositories\ForgetPasswordRepository;
use App\Libraries\ResponseLib;
use App\Enums\Functions;
use App\Enums\RoleGroup;
use App\Enums\OpCenter;
use App\Enums\Area;
use App\Mail\ForgetPassword;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Exception;
use LdapRecord\Connection;
use LdapRecord\Query\Filter\Parser;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Carbon;

class ForgetPasswordService
{
	const EXPIRED_MINS = 10;
	
	public function __construct(protected ForgetPasswordRepository $_repository)
	{
	}
	
	/* Mail forget password link
	 * @params: 
	 * @return: boolean
	 */
	public function sendLink($account)
	{
		try
		{
			Log::channel('webSysLog')->info("使用者[{$account}]忘記密碼", [ __class__, __function__, __line__]);
			
			$userInfo = $this->_authUserAccount($account);
			
			$link = $this->_createLink($userInfo);
			
			$this->_sendMail($userInfo['email'], $link);
			
			$msg = "忘記密碼連結已發送至帳號 {$account} 所屬信箱";
			Log::channel('webSysLog')->info($msg, [ __class__, __function__, __line__]);
			
			return ResponseLib::initialize()->success($msg);
		}
		catch(Exception $e)
		{
			Log::channel('webSysLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			return ResponseLib::initialize()->fail($e->getMessage());
		}
	}
	
	/* 驗證帳號並取mail
	 * @params: string
	 * @return: mixed
	 */
	private function _authUserAccount($account)
	{
		$info = $this->_repository->getUserByAccount($account);
		
		if (empty($info))
			throw new Exception('Mail發送失敗，此帳號為無效帳號');
		
		$isActive = boolval($info['isActive']);
		
		if ($isActive == FALSE)
			throw new Exception('此帳號已停用');
		
		if (empty($info['email']))
			throw new Exception('此帳號未設定Mail信箱');
		
		return $info;
	}
	
	/* Create link
	 * @params: string
	 * @return: mixed
	 */
	private function _createLink($userInfo)
	{
		$userId = $userInfo['userId'];
		$token	= Str::uuid7();
		
		$link = route('forgetPassword.setting', ['token' => $token]);
		
		$result = $this->_repository->insert($token, $userId, self::EXPIRED_MINS);
		
		if ($result == FALSE)
			throw new Exception('忘記密碼發送初始化設定失敗，請稍候再試');
		
		return $link;
	}
	
	/* Create link
	 * @params: string
	 * @return: mixed
	 */
	private function _sendMail($to, $link)
	{
		try 
		{
            Mail::to($to)->send(new ForgetPassword($link, self::EXPIRED_MINS));
            
			Log::channel('webSysLog')->error("郵件發送成功({$to})", [ __class__, __function__, __line__]);
            return TRUE;
        } 
		catch (Exception $e) 
		{
            #如果 SMTP 連線失敗或退信，記錄 Log 避免整個網頁崩潰
			Log::channel('webSysLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception("Mail發送失敗，請稍候再試");
        }
		
	}
	
	public function getInfoByToken($token)
	{
		$result = $this->_repository->getInfoByToken($token);
		
		$info['id']			= $result['userId'];
		$info['account']	= $result['userAccount'];
		$info['name']		= $result['userDisplayName'];
		$info['expiredTime']= $result['expiredAt'];
		
		$expired = Carbon::parse($info['expiredTime']);
		
		if (now()->greaterThan($expired))
			return ResponseLib::initialize()->fail('此驗證連結已過期，請重新執行忘記密碼');
		else
			return ResponseLib::initialize($info)->success();
	}
	
	/* Update password by forget password
	 * @params: string
	 * @return: mixed
	 */
	public function updatePassword($userId, $password, $account = '')
	{
		try 
		{
			if (empty($userId) OR empty($password))
				throw new Exception('新密碼變更失敗，無法識別使用者帳號，請重新執行');
			
            $password = Hash::make($password);
			$result = $this->_repository->updatePasswordByUserId($userId, $password);
			
			if ($result === FALSE)
				throw new Exception('新密碼變更失敗，請重新執行');
			
			$user = empty($account) ? $userId : $account;
			
			Log::channel('webSysLog')->info("使用者({$user})已執行密碼變更", [ __class__, __function__, __line__]);
            return ResponseLib::initialize()->success();
        } 
		catch (Exception $e) 
		{
            Log::channel('webSysLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			return ResponseLib::initialize()->fail($e->getMessage());
        }
	}
}
