<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\ForgetPasswordService;
use App\ViewModels\ForgetPasswordViewModel;
use App\Libraries\ResponseLib;
use App\Enums\FormAction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ForgetPasswordController extends Controller
{
	public function __construct(protected ForgetPasswordService $_service, protected ForgetPasswordViewModel $_viewModel)
	{
	}
	
	/* 忘記密碼 Called by login view
	 * @params: request
	 * @return: view
	 */
	public function sendLink(Request $request)
	{
		#此功能不存viewModel
		$account = $request->input('account');
		
		$validator = Validator::make($request->all(), [
            'account' => 'required|max:20',
		]);
		
		if ($validator->fails())
			return redirect()->route('signin')->with('msg', '忘記密碼連結發送失敗：使用者帳號未輸入');
		
		$response = $this->_service->sendLink($account);
	
		#不管成功或失敗都是回到登入頁
		$msg = $response->msg;

		return redirect()->route('signin')->with('msg', $msg);
	}
	
	/* 
	 * @params: request
	 * @return: view
	 */
	public function showSetting($token)
	{
		$this->_viewModel->initialize(FormAction::UPDATE);
		$response = $this->_service->getInfoByToken($token);
		
		if ($response->status === FALSE)
			return redirect()->route('signin')->with('msg', $response->msg);
		else
		{
			$data = $response->data;
			$this->_viewModel->keepFormData($data['id'], $data['account'], $data['name']);
			
			return view('forget_password_setting')->with('viewModel', $this->_viewModel);
		}
	}
	
	/* 登入驗證
	 * @params: request
	 * @return: view
	 */
	public function setting(Request $request)
	{
		$userId 	= $request->input('id');
		$account 	= $request->input('account'); #為了要暫存
		$name 		= $request->input('name'); #為了要暫存
		$password	= $request->input('password');
		
		$this->_viewModel->initialize(FormAction::UPDATE);
		$this->_viewModel->keepFormData($userId, $account, $name);
		
		$validator = Validator::make($request->all(), [
            'id' => 'required',
			'password' => 'required|max:20',
        ]);
		
		if ($validator->fails())
		{
			$this->_viewModel->fail('設定失敗，無法識別帳號ID');
			return view('forget_password_setting')->with('viewModel', $this->_viewModel);
		}
		
		$response = $this->_service->updatePassword($userId, $password, $account); #account當備用輸入,故放最後
		
		if ($response->status === FALSE)
		{
			$this->_viewModel->fail($response->msg);
			return view('forget_password_setting')->with('viewModel', $this->_viewModel);
		}
		else
			return redirect()->route('signin')->with('msg', '使用者密碼變更完成，請重新登入');
	}
}