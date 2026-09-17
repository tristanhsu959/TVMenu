<?php

namespace App\Socialite;

use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\ProviderInterface;
use Laravel\Socialite\Two\User;
use SocialiteProviders\Manager\ConfigTrait; #use for set config

class WebcommProvider extends AbstractProvider implements ProviderInterface
{
	use ConfigTrait;
	
    #偉康 OIDC 標準要求的 Scopes
	#protected $scopes = []; #不預設,因不知是什麼
	protected $scopeSeparator = ' '; #要以空格分隔,預設是逗號
	
    protected $scopes = [
		'profile',
		'email',
		'openid',
	];
	
	public static function additionalConfigKeys()
    {
		#會將config/service.php存在內建的$this->config
        return ['base_url'];
    }
	
    /* 授權登入網址-Called by redirect
	 * 
	 * @param  string  $code
	 * @return array
	 */
    protected function getAuthUrl($state)
    {
		$baseUrl = rtrim($this->config['base_url'], '/');
		$authUrl = "{$baseUrl}/protocol/openid-connect/auth";
	
        return $this->buildAuthUrlFromBase($authUrl, $state);
    }
	
	/**Called by callback
	 * 組合發送至 Token 交換網址的 Body 參數
	 * 
	 * @param  string  $code
	 * @return array
	 */
	protected function getTokenFields($code)
	{
		return [
			'grant_type'    => 'authorization_code',
			'code'          => $code, 
			'client_id'     => $this->clientId,
			'client_secret' => $this->clientSecret,
			'redirect_uri'  => $this->redirectUrl,
		];
	}

    /* Token 交換網址-Called by callback(Socialite::driver('webcomm')->user())
	 * 
	 * @param  string  $code
	 * @return array
	 */
    protected function getTokenUrl()
    {
		$baseUrl	= rtrim($this->config['base_url'], '/');
		$tokenUrl 	= "{$baseUrl}/protocol/openid-connect/token";
		
        return $tokenUrl;
    }
	
	#Called by callback
	#取得使用者資料網址 (Userinfo)
    protected function getUserByToken($token)
    {
		$baseUrl = rtrim($this->config['base_url'], '/');
        $userUrl = "{$baseUrl}/protocol/openid-connect/userinfo";

        $response = $this->getHttpClient()->get($userUrl, [
            'headers' => [
                'Authorization' => "Bearer {$token}",
                'Accept' => 'application/x-www-form-urlencoded', #'application/json',
            ],
        ]);

        return json_decode($response->getBody(), true);
    }
	
	#Called by callback
    #將偉康回傳的 JSON 欄位對應至 Socialite 的 User 物件
    protected function mapUserToObject(array $user)
    {
        return (new User)->setRaw($user)->map([
            'id'       => $user['sub'] ?? null,                          #唯一識別碼
            'nickname' => $user['preferred_username'] ?? null,           #帳號
            'name'     => $user['name'] ?? $user['given_name'] ?? null,  #姓名
            'email'    => $user['email'] ?? null,                        #電子郵件
        ]);
    }

}
