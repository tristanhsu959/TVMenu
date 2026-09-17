<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\NewReleaseSettingService;
use App\ViewModels\NewReleaseSettingViewModel;
use App\Enums\FormAction;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class NewReleaseSettingController extends Controller
{
	
	public function __construct(protected NewReleaseSettingService $_service, protected NewReleaseSettingViewModel $_viewModel)
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
		
		return view('new_release_setting/list')->with('viewModel', $this->_viewModel);
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
		
		return view('new_release_setting/detail')->with('viewModel', $this->_viewModel);
	}
	
	/* 新增 POST
	 * @params: request
	 * @return: view
	 */
	public function create(Request $request)
	{
		#fetch form data
		$id				= $request->integer('id');
		$brandId		= $request->integer('brandId', 0);
		$productIds		= $request->array('productIds', []);
		$name			= $request->input('name');
		$saleDate		= $request->input('saleDate');
		$tasteKeyWord	= $request->input('tasteKeyWord');
		$status			= $request->boolean('status', FALSE);
		
		$this->_viewModel->initialize(FormAction::CREATE);
		$this->_viewModel->keepFormData($id, $brandId, $productIds, $name, $saleDate, $tasteKeyWord, $status);
		
		#validate input
		$validator = Validator::make($request->all(), [
			'brandId' 	=> 'required|integer',
			'productIds'=> 'required',
            'name' 		=> 'required|max:30',
			'saleDate' 	=> 'required|date_format:Y-m-d',
        ]);
 
        if ($validator->fails()) 
		{
			$this->_viewModel->fail('資料輸入不完整');
			return view('new_release_setting/detail')->with('viewModel', $this->_viewModel);
		}
		
		$response = $this->_service->createNewRelease($id, $brandId, $productIds, $name, $saleDate, $tasteKeyWord, $status);
		
		if ($response->status === FALSE)
		{
			$this->_viewModel->fail($response->msg);
			return view('new_release_setting/detail')->with('viewModel', $this->_viewModel);
		}
		else
			return redirect()->route('new_release_setting.list')->with('msg', '新品設定完成');
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
			return redirect()->route('new_release_setting.list')->with('msg', '新品識別ID為空值');
		
		$response = $this->_service->getNewReleaseById($id);
		
		if ($response->status === FALSE)
			return redirect()->route('new_release_setting.list')->with('msg', $response->msg);
		
		$data = $response->data; 
		$this->_viewModel->keepFormData($data['releaseId'], $data['releaseBrandId'], $data['productIds'], 
			$data['releaseName'], $data['releaseSaleDate'], $data['releaseTaste'],  $data['releaseStatus'], $data['updateAt']);
		$this->_viewModel->success();
		
		return view('new_release_setting/detail')->with('viewModel', $this->_viewModel);
	}
	
	/* 編輯Form
	 * @params: request
	 * @return: view
	 */
	public function update(Request $request)
	{
		$id				= $request->integer('id');
		$brandId		= $request->integer('brandId', 0);
		$productIds		= $request->array('productIds', []);
		$name			= $request->input('name');
		$saleDate		= $request->input('saleDate');
		$tasteKeyWord	= $request->input('tasteKeyWord');
		$status			= $request->boolean('status', FALSE);
		
		$this->_viewModel->initialize(FormAction::UPDATE);
		$this->_viewModel->keepFormData($id, $brandId, $productIds, $name, $saleDate, $tasteKeyWord, $status);
		
		if (empty($id))
			return redirect()->route('new_release_setting.list')->with('msg', '新品識別ID為空值');
		
		#validate input
		$validator = Validator::make($request->all(), [
			'id' 		=> 'required|integer',
			'brandId' 	=> 'required|integer',
			'productIds'=> 'required',
            'name' 		=> 'required|max:30',
			'saleDate' 	=> 'required|date_format:Y-m-d',
        ]);
		
		if ($validator->fails()) 
		{
			$this->_viewModel->fail('資料輸入不完整');
			return view('new_release_setting/detail')->with('viewModel', $this->_viewModel);
		}
		
		$response = $this->_service->updateNewRelease($id, $brandId, $productIds, $name, $saleDate, $tasteKeyWord, $status);
		
		if ($response->status === FALSE)
		{
			$this->_viewModel->fail($response->msg);
			return view('new_release_setting/detail')->with('viewModel', $this->_viewModel);
		}
		else
			return redirect()->route('new_release_setting.list')->with('msg', '新品編輯完成');
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
			return redirect()->route('new_release_setting.list')->with('msg', '新品識別ID為空值');
		
		$response = $this->_service->deleteNewRelease($id);
		
		if ($response->status === FALSE)
			return redirect()->route('new_release_setting.list')->with('msg', $response->msg);
		else
			return redirect()->route('new_release_setting.list')->with('msg', '新品刪除完成');
	}
}
