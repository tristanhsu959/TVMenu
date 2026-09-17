<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\ProductService;
use App\ViewModels\ProductViewModel;
use App\Enums\FormAction;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
	
	public function __construct(protected ProductService $_service, protected ProductViewModel $_viewModel)
	{
	}
	
	/* POS料號列表
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
		
		return view('product/list')->with('viewModel', $this->_viewModel);
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
		
		return view('product/detail')->with('viewModel', $this->_viewModel);
	}
	
	/* 新增 POST
	 * @params: request
	 * @return: view
	 */
	public function create(Request $request)
	{
		#fetch form data
		$id			= $request->input('id');
		$brandId	= $request->integer('brandId', 0);
		$category	= $request->integer('category', 0);
		$name		= $request->input('name');
		$primaryNo	= $request->input('primaryNo');
		$secondaryNo= $request->input('secondaryNo');
		
		#initialize
		$this->_viewModel->initialize(FormAction::CREATE);
		$this->_viewModel->keepFormData($id, $category, $brandId, $name, $primaryNo, $secondaryNo);
		
		#validate input
		$validator = Validator::make($request->all(), [
			'brandId' => 'required|integer',
            'name' => 'required|max:15',
			'primaryNo' => 'required',
        ]);
 
        if ($validator->fails()) 
		{
			$this->_viewModel->fail('資料輸入不完整');
			return view('product/detail')->with('viewModel', $this->_viewModel);
		}
		
		$response = $this->_service->createProduct($brandId, $category, $name, $primaryNo, $secondaryNo);
		
		if ($response->status === FALSE)
		{
			$this->_viewModel->fail($response->msg);
			return view('product/detail')->with('viewModel', $this->_viewModel);
		}
		else
			return redirect()->route('product.list')->with('msg', '產品設定完成');
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
			return redirect()->route('product.list')->with('msg', '產品識別ID為空值');
		
		$response = $this->_service->getProductById($id);
		
		if ($response->status === FALSE)
			return redirect()->route('product.list')->with('msg', $response->msg);
		
		$data = $response->data; 
		$this->_viewModel->keepFormData($data['productId'], $data['productBrandId'], $data['productCategory'], $data['productName'], $data['primaryNo'], $data['secondaryNo']);
		$this->_viewModel->success();
		
		return view('product/detail')->with('viewModel', $this->_viewModel);
	}
	
	/* 編輯Form
	 * @params: request
	 * @return: view
	 */
	public function update(Request $request)
	{
		$id			= $request->input('id');
		$brandId	= $request->integer('brandId', 0);
		$category	= $request->integer('category', 0);
		$name		= $request->input('name');
		$primaryNo	= $request->input('primaryNo');
		$secondaryNo= $request->input('secondaryNo');
		
		#initialize
		$this->_viewModel->initialize(FormAction::UPDATE);
		$this->_viewModel->keepFormData($id, $brandId, $category, $name, $primaryNo, $secondaryNo);
		
		if (empty($id))
			return redirect()->route('product.list')->with('msg', '產品識別ID為空值');
		
		$validator = Validator::make($request->all(), [
			'id' => 'required|integer',
            'brandId' => 'required|integer',
            'name' => 'required|max:15',
			'primaryNo' => 'required',
        ]);
 
        if ($validator->fails()) 
		{
			$this->_viewModel->fail('資料輸入不完整');
			return view('product/detail')->with('viewModel', $this->_viewModel);
		}
		
		$response = $this->_service->updateProduct($id, $brandId, $category, $name, $primaryNo, $secondaryNo);
		
		if ($response->status === FALSE)
		{
			$this->_viewModel->fail($response->msg);
			return view('product/detail')->with('viewModel', $this->_viewModel);
		}
		else
			return redirect()->route('product.list')->with('msg', '產品編輯完成');
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
			return redirect()->route('product.list')->with('msg', '產品識別ID為空值');
		
		$response = $this->_service->deleteProduct($id);
		
		if ($response->status === FALSE)
			return redirect()->route('product.list')->with('msg', $response->msg);
		else
			return redirect()->route('product.list')->with('msg', '產品刪除完成');
	}
}
