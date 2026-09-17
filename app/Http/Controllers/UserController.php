<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\UserService;
use App\ViewModels\UserViewModel;
use App\Enums\FormAction;
use App\Enums\Area;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
	
	public function __construct(protected UserService $_service, protected UserViewModel $_viewModel)
	{
	}
	
	/* 列表
	 * @params: request
	 * @return: view
	 */
	public function list(Request $request)
	{
		$this->_viewModel->initialize(FormAction::LIST);
		
		$response = $this->_service->getList();
		
		if ($response->status === FALSE)
			$this->_viewModel->fail($response->msg);
		else
		{
			$this->_viewModel->success();
			$this->_viewModel->list = $response->data;
		}
		
		return view('user/list')->with('viewModel', $this->_viewModel);
	}
	
	/* 新增Form
	 * @params: request
	 * @return: view
	 */
	public function showCreate(Request $request)
	{
		#initialize
		$this->_viewModel->initialize(FormAction::CREATE);
		$this->_viewModel->keepFormData(); #init
		$this->_viewModel->success();
		
		return view('user/detail')->with('viewModel', $this->_viewModel);
	}
	
	/* 新增 POST
	 * @params: request
	 * @return: view
	 */
	public function create(Request $request)
	{
		#fetch form data
		$id				= $request->input('id');
		$account		= $request->input('account');
		$password		= $request->input('password');
		$displayName	= $request->input('displayName');
		$department		= $request->input('department');
		$email			= $request->input('email');
		$description	= $request->input('description');
		$isActive		= $request->boolean('isActive');
		$permission		= $request->array('permission', []);
		$areaPermission	= $request->array('area', []);
		
		#initialize
		$this->_viewModel->initialize(FormAction::CREATE);
		$this->_viewModel->keepFormData($id, $account, $password, $displayName,
											$department, $email, $description, $isActive, 
											$permission, $areaPermission);
		
		#validate input
		$validator = Validator::make($request->all(), [
            'account' 	=> 'required|max:20',
			'password' 	=> 'required|min:6',
			'email' 	=> 'nullable|email', 
        ]);
 
        if ($validator->fails()) 
		{
			$this->_viewModel->fail('資料輸入不完整或密碼，Mail輸入格式錯誤');
			return view('user/detail')->with('viewModel', $this->_viewModel);
		}
		
		$response = $this->_service->createUser($account, $password, $displayName, $department, $email, $description, $isActive, 
													$permission, $areaPermission);
		
		if ($response->status === FALSE)
		{
			$this->_viewModel->fail($response->msg);
			return view('user/detail')->with('viewModel', $this->_viewModel);
		}
		else
			return redirect()->route('user.list')->with('msg', '新增帳號完成');
	}
	
	/* 編輯Form
	 * @params: request
	 * @params: int	id
	 * @return: view
	 */
	public function showUpdate(Request $request, $id)
	{
		#initialize
		$this->_viewModel->initialize(FormAction::UPDATE);
		
		if (empty($id))
			return redirect()->route('user.list')->with('msg', '身份識別ID為空值');
		
		$response = $this->_service->getUserById($id);
		
		if ($response->status === FALSE)
			return redirect()->route('user.list')->with('msg', $response->msg);
		
		$data = $response->data;
		$this->_viewModel->keepFormData($data['userId'], $data['userAccount'], '', $data['userDisplayName'],
								$data['department'], $data['email'], $data['description'], $data['isActive'],
								$data['rolePermission'], $data['roleArea'], 
								$data['updateAt'], empty($data['userPassword']) ? FALSE : TRUE);
		
		$this->_viewModel->success();
		
		return view('user/detail')->with('viewModel', $this->_viewModel);
	}
	
	/* 編輯Form
	 * @params: request
	 * @return: view
	 */
	public function update(Request $request)
	{
		#fetch form data
		$id				= $request->input('id');
		$account		= $request->input('account');
		$password		= $request->input('password');
		$displayName	= $request->input('displayName');
		$department		= $request->input('department');
		$email			= $request->input('email');
		$description	= $request->input('description');
		$isActive		= $request->boolean('isActive');
		$permission		= $request->array('permission');
		$areaPermission	= $request->array('area', []);
		
		#initialize
		$this->_viewModel->initialize(FormAction::UPDATE);
		$this->_viewModel->keepFormData($id, $account, $password, $displayName,
											$department, $email, $description, $isActive, 
											$permission, $areaPermission);
		
		if (empty($id))
			return redirect()->route('user.list')->with('msg', '身份識別ID為空值');
		
		#validate input
		$validator = Validator::make($request->all(), [
            'account' 	=> 'required|max:20',
			'email' 	=> 'nullable|email', 
        ]);
		
		if ($validator->fails()) 
		{
			$this->_viewModel->fail('資料輸入不完整或Mail格式錯誤');
			return view('user/detail')->with('viewModel', $this->_viewModel);
		}
		
		$response = $this->_service->updateUser($id, $account, $password, $displayName, $department, $email, $description, $isActive, 
													$permission, $areaPermission);
		
		if ($response->status === FALSE)
		{
			$this->_viewModel->fail($response->msg);
			return view('user/detail')->with('viewModel', $this->_viewModel);
		}
		else
			return redirect()->route('user.list')->with('msg', '編輯帳號完成');
	}
	
	/* 刪除
	 * @params: request
	 * @params: int	id
	 * @return: view
	 */
	public function delete(Request $request, $id)
	{
		#initialize
		$this->_viewModel->initialize(FormAction::DELETE, $id);
		
		/*跟validator整併即可*/
		if (empty($id))
			return redirect()->route('user.list')->with('msg', '身份識別ID為空值');
		
		$response = $this->_service->deleteUser($id);
		
		if ($response->status === FALSE)
			return redirect()->route('user.list')->with('msg', $response->msg);
		else
			return redirect()->route('user.list')->with('msg', '刪除帳號完成');
	}
}
