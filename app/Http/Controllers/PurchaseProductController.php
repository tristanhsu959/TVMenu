<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\PurchaseProductService;
use App\ViewModels\PurchaseProductViewModel;
use App\Enums\FormAction;
use App\Enums\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

class PurchaseProductController extends Controller
{
	
	public function __construct(protected PurchaseProductService $_service, protected PurchaseProductViewModel $_viewModel)
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
		
		return view('purchase_product/list')->with('viewModel', $this->_viewModel);
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
			return redirect()->route('purchase_product.list')->with('msg', $response->msg);
		
		$data = $response->data; 
		
		$this->_viewModel->keepFormData($data);
		$this->_viewModel->success();
		
		return view('purchase_product/setting')->with('viewModel', $this->_viewModel);
	}
	
	/* 編輯Form
	 * @params: request
	 * @return: view
	 */
	public function update(Request $request)
	{
		#設定所有brand, 故不用取brandId
		$productCodes	= $request->array('productCodes');
		
		$this->_viewModel->initialize(FormAction::UPDATE);
		$this->_viewModel->keepFormData($productCodes);
		
		$response = $this->_service->updateSetting($productCodes);
		
		if ($response->status === FALSE)
		{
			$this->_viewModel->fail($response->msg);
			return view('purchase_product/setting')->with('viewModel', $this->_viewModel);
		}
		else
			return redirect()->route('purchase_product.list')->with('msg', '產品設定完成');
	}
	
	
}
