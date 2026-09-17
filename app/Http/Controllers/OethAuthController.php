<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use App\Libraries\ResponseLib;
use App\Enums\FormAction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Laravel\Socialite\Facades\Socialite;

class OethAuthController extends Controller
{
	public function __construct(protected AuthService $_service)
	{
	}
	
	/* Redirect to webcomm
	 * @params: request
	 * @return: view
	 */
	public function redirect()
	{
		return Socialite::driver('webcomm')->scopes(['openid'])->redirect();
	}
	
	/* Webcomm oidc auth callback
	 * @params: request
	 * @return: view
	 */
	public function callback()
	{
		
		$response = $this->_service->oethAuth();
		
		if ($response->status === FALSE)
			return redirect()->route('signin')->with('msg', $response->msg);
		else
			return redirect('home');
	}
}