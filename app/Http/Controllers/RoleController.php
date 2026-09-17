<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\RoleService;
use App\ViewModels\RoleViewModel;
use App\Enums\FormAction;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class RoleController extends Controller
{
	public function __construct(protected RoleService $_service, protected RoleViewModel $_viewModel)
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
		
		return view('role/list')->with('viewModel', $this->_viewModel);
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
		
		return view('role/detail')->with('viewModel', $this->_viewModel);
	}
	
	/* 新增 POST
	 * @params: request
	 * @return: view
	 */
	public function create(Request $request)
	{
		#fetch form data
		$id 		= $request->input('id');
		$name 		= $request->input('name');
		$group 		= $request->input('group');
		$permission	= $request->input('permission');
		$area		= $request->input('area');
		
		#initialize
		$this->_viewModel->initialize(FormAction::CREATE);
		$this->_viewModel->keepFormData($id, $name, $permission, $area);
		
		#validate input
		$validator = Validator::make($request->all(), [
            'name' => 'required|max:20',
        ]);
 
        if ($validator->fails()) 
		{
			$this->_viewModel->fail('資料輸入不完整');
			return view('role/detail')->with('viewModel', $this->_viewModel);
		}
		
		$response = $this->_service->createRole($name, $group, $permission, $area);
		
		if ($response->status === FALSE)
		{
			$this->_viewModel->fail($response->msg);
			return view('role/detail')->with('viewModel', $this->_viewModel);
		}
		else
			return redirect()->route('role.list')->with('msg', '新增身份完成');;
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
			return redirect()->route('role.list')->with('msg', '身份識別ID為空值');
		
		$response = $this->_service->getRoleById($id);
		
		if ($response->status === FALSE)
			return redirect()->route('role.list')->with('msg', $response->msg);
		
		$data = $response->data;
		$this->_viewModel->keepFormData($data['roleId'], $data['roleName'], $data['rolePermission'], $data['roleArea'], $data['roleGroup'], $data['updateAt']);
		$this->_viewModel->success();
		
		return view('role/detail')->with('viewModel', $this->_viewModel);
	}
	
	/* 編輯Form
	 * @params: request
	 * @return: view
	 */
	public function update(Request $request)
	{
		#fetch form data
		$id 		= $request->input('id');
		$name 		= $request->input('name');
		$group 		= $request->input('group');
		$permission	= $request->array('permission');
		$area		= $request->array('area');
		
		$this->_viewModel->keepFormData($id, $name, $permission, $area, $group);
		
		if (empty($id))
			return redirect()->route('role.list')->with('msg', '身份識別ID為空值');
		
		$validator = Validator::make($request->all(), [
            'name' => 'required|max:20',
        ]);
 
        if ($validator->fails()) 
		{
			$this->_viewModel->fail('資料輸入不完整');
			return view('role/detail')->with('viewModel', $this->_viewModel);
		}
		
		$response = $this->_service->updateRole($id, $name, $group, $permission, $area);
		
		if ($response->status === FALSE)
		{
			$this->_viewModel->fail($response->msg);
			return view('role/detail')->with('viewModel', $this->_viewModel);
		}
		else
			return redirect()->route('role.list')->with('msg', '編輯身份完成');
	}
	
	/* 刪除
	 * @params: request
	 * @params: int	id
	 * @return: view
	 */
	public function delete(Request $request, $id)
	{
		#initialize
		$this->_viewModel->initialize(FormAction::DELETE);
		
		/*跟validator整併即可*/
		if (empty($id))
			return redirect()->route('role.list')->with('msg', '身份識別ID為空值');
		
		$response = $this->_service->deleteRole($id);
		
		if ($response->status === FALSE)
			return redirect()->route('role.list')->with('msg', $response->msg);
		else
			return redirect()->route('role.list')->with('msg', '刪除身份完成');
	}
}
