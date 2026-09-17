<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use App\ViewModels\AuthViewModel;
use App\Libraries\ResponseLib;
use App\Enums\FormAction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
	public function __construct(protected AuthService $_service, protected AuthViewModel $_viewModel)
	{
	}
	
	/* Signin view
	 * @params: request
	 * @return: view
	 */
	public function showSignin()
	{
		#自動登出,避免view載入錯誤
		$this->_service->signout();
		$this->_viewModel->action = FormAction::SIGNIN;
		session()->put('botTimeValidate', now());
		
		return view('signin')->with('viewModel', $this->_viewModel);
	}
	
	/* 登入驗證
	 * @params: request
	 * @return: view
	 */
	public function signin(Request $request)
	{
		$account 	= $request->input('account');
		$password	= $request->input('password');
		$captcha	= $request->input('captcha');
		
		$this->_viewModel->action = FormAction::SIGNIN;
		$this->_viewModel->keepFormData($account); #account only
		
		$validator = Validator::make($request->all(), [
            'account' => 'required|max:20',
			'password' => 'required|max:20',
        ]);
		
		$botSt = session()->get('botTimeValidate');
		
		if (! empty($captcha) OR $botSt->diffInSeconds(now()) < 1)
			 abort(400, 'Bad Request');
		
        if ($validator->fails())
		{
			$this->_viewModel->fail('登入失敗，帳號或密碼輸入不完整');
			return view('signin')->with('viewModel', $this->_viewModel);
		}
		
		$response = $this->_service->signin($account, $password);
		
		if ($response->status === FALSE)
		{
			$this->_viewModel->fail($response->msg);
			return view('signin')->with('viewModel', $this->_viewModel);
		}
		else
			return redirect('home');
	}
	
	/* Signout
	 * @params: request
	 * @return: view
	 */
	public function signout(Request $request)
	{
		$this->_viewModel->action = FormAction::SIGNIN;
		$this->_service->signout();
		
		return redirect('signin');
		#return view('signin')->with('viewModel', $this->_viewModel);
	}
	
}