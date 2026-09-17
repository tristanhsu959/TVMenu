<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\PurchaseProductInfoService;
use App\ViewModels\PurchaseProductInfoViewModel;
use App\Enums\Brand;
use App\Enums\FormAction;
use App\Enums\Functions;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PurchaseProductInfoController extends Controller
{
	public function __construct(protected PurchaseProductInfoService $_service, protected PurchaseProductInfoViewModel $_viewModel)
	{
	}
	
	public function showSearch(Request $request)
	{
		$brand 		= $this->_service->parsingBrand($request->segments());
		$function 	= $this->_service->parsingFunction($brand);
		
		$this->_viewModel->initialize($brand, $function);
		$this->_viewModel->keepSearchData();
		
		if (empty($brand) OR empty($function))
			$this->_viewModel->fail('無法識別ID');
		
		return view('purchase_product_info.index')->with('viewModel', $this->_viewModel);
	}
	
	/* Search
	 * @params: request
	 * @return: view
	 */
	public function search(Request $request)
	{
		$brand 		= $this->_service->parsingBrand($request->segments());
		$function 	= $this->_service->parsingFunction($brand);
		
		$searchType 		= $request->input('searchType');
		$searchProductTypes = $request->array('searchProductTypes');
		$searchFactoryIds 	= $request->array('searchFactoryIds'); #門店/工廠
		$searchOffShelf 	= $request->boolean('searchOffShelf');
		$searchShortCode 	= $request->input('searchShortCode');
		$searchProductName 	= $request->input('searchProductName');
		
		$this->_viewModel->initialize($brand, $function);
		$this->_viewModel->keepSearchData($searchType, $searchProductTypes, $searchFactoryIds, $searchOffShelf, $searchShortCode, $searchProductName); 
		
		#validate input
		$validator = Validator::make($request->all(), [
			'searchType' 	=> 'required',
        ]);
 
        if ($validator->fails()) 
		{
			$this->_viewModel->fail('查詢參數錯誤');
			return view('purchase_product_info.index')->with('viewModel', $this->_viewModel);
		}
		
		$response = $this->_service->getStatistics($brand, $searchType, $searchProductTypes, $searchFactoryIds, $searchOffShelf, $searchShortCode, $searchProductName);
		
		if ($response->status === FALSE)
			$this->_viewModel->fail($response->msg);
		else
			$this->_viewModel->success();
		
		$this->_viewModel->statistics = $response->data; 
		
		return view('purchase_product_info.index')->with('viewModel', $this->_viewModel); 
	}
	
	/* Export
	 * @params: request
	 * @return: view
	 */
	public function export(Request $request, $token)
	{
		$brand 		= $this->_service->parsingBrand($request->segments());
		$function 	= $this->_service->parsingFunction($brand);
		
		$this->_viewModel->initialize($brand, $function);
		$this->_viewModel->keepSearchData(); #預防錯誤的預設
		
		$response = $this->_service->export($token);
		
		if ($response->status === FALSE)
		{
			$this->_viewModel->fail($response->msg);
			return view('purchase_product_info.index')->with('viewModel', $this->_viewModel);
		}
		else
		{
			$fileName = $response->data; 
			$filePath = Storage::disk('export')->path($fileName);
			
			if (file_exists($filePath)) {
				return response()->download($filePath)->deleteFileAfterSend();
			}
		}
	}
}
