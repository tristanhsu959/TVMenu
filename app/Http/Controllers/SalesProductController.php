<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\SalesProductService;
use App\ViewModels\SalesProductViewModel;
use App\Enums\FormAction;
use App\Enums\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

class SalesProductController extends Controller
{
	
	public function __construct(protected SalesProductService $_service, protected SalesProductViewModel $_viewModel)
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
		
		return view('sales_product/list')->with('viewModel', $this->_viewModel);
	}
	
	/* 編輯Form
	 * @params: request
	 * @params: int	id
	 * @return: view
	 */
	public function showUpdate(Request $request)
	{
		#initialize
		$this->_viewModel->initialize(FormAction::UPDATE);
		
		$response = $this->_service->getSetting();
		
		if ($response->status === FALSE)
			return redirect()->route('sales_product.list')->with('msg', $response->msg);
		
		$data = $response->data; 
		
		$this->_viewModel->keepFormData($data);
		$this->_viewModel->success();
		
		return view('sales_product/setting')->with('viewModel', $this->_viewModel);
	}
	
	/* 編輯Form
	 * @params: request
	 * @return: view
	 */
	public function update(Request $request)
	{
		#設定所有brand, 故不用取brandId
		$productIds	= $request->array('productIds');
		
		$this->_viewModel->initialize(FormAction::UPDATE);
		$this->_viewModel->keepFormData($productIds);
		
		$response = $this->_service->updateSetting($productIds);
		
		if ($response->status === FALSE)
		{
			$this->_viewModel->fail($response->msg);
			return view('sales_product/setting')->with('viewModel', $this->_viewModel);
		}
		else
			return redirect()->route('sales_product.list')->with('msg', '產品設定完成');
	}
	
	
}
