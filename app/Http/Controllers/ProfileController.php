<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\UserService;
use App\Enums\FormAction;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
	
	public function __construct(protected UserService $_service)
	{
	}
	
	/* 編輯Form
	 * @params: request
	 * @return: view
	 */
	public function update(Request $request)
	{
		#fetch form data
		$id			= $request->input('id');
		$password	= $request->input('password');
		$displayName= $request->input('displayName');
		$department	= $request->input('department');
		$email		= $request->input('email');
		
		if (empty($id))
			return redirect()->route('home')->with('msg', '身份識別ID為空值');
		
		#都非必要欄位, 空值照樣更新
		$response = $this->_service->updateProfile($id, $password, $displayName, $department, $email);
		
		if ($response->status === FALSE)
			return redirect()->route('home')->with('msg', '使用者Profile更新失敗');
		else
			return redirect()->route('home')->with('msg', '使用者Profile更新完成');
	}
}
