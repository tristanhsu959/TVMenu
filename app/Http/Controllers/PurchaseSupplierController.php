<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\PurchaseSupplierService;
use App\ViewModels\PurchaseSupplierViewModel;
use App\Enums\Brand;
use App\Enums\FormAction;
use App\Enums\Functions;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PurchaseSupplierController extends Controller
{
	public function __construct(protected PurchaseSupplierService $_service, protected PurchaseSupplierViewModel $_viewModel)
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
		
		return view('purchase_supplier.statistics')->with('viewModel', $this->_viewModel);
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
		$searchStDate 		= $request->input('searchStDate');
		$searchEndDate 		= $request->input('searchEndDate');
		$searchAreaIds 		= $request->array('searchAreaIds');
		$searchWhere 		= $request->input('searchWhere');
		$searchKeyword		= $request->input('searchKeyword');
		$searchCategory 	= $request->input('searchCategory');
		$searchProductIds 	= $request->array('searchProductIds');
		
		$this->_viewModel->initialize($brand, $function);
		$this->_viewModel->keepSearchData($searchType, $searchStDate, $searchEndDate, $searchAreaIds, 
							$searchWhere, $searchKeyword, $searchCategory, $searchProductIds); 
		
		#validate input
		$validator = Validator::make($request->all(), [
			'searchStDate' 		=> 'required|date_format:Y-m-d',
			'searchEndDate'		=> 'required|date_format:Y-m-d',
			'searchKeyword'		=> 'required_without:searchProductIds',
			'searchProductIds'	=> 'required_without:searchKeyword',
        ]);
 
        if ($validator->fails()) 
		{
			$this->_viewModel->fail('查詢參數錯誤');
			return view('purchase_supplier.statistics')->with('viewModel', $this->_viewModel);
		}
		
		$response = $this->_service->getStatistics($brand, $searchType, $searchStDate, $searchEndDate, $searchAreaIds, 
							$searchWhere, $searchKeyword, $searchCategory, $searchProductIds);
		
		if ($response->status === FALSE)
			$this->_viewModel->fail($response->msg);
		else
			$this->_viewModel->success();
		
		$this->_viewModel->statistics = $response->data; 
		
		return view('purchase_supplier.statistics')->with('viewModel', $this->_viewModel); 
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
		$this->_viewModel->keepSearchData();
		
		$response = $this->_service->export($token);
		
		if ($response->status === FALSE)
		{
			$this->_viewModel->fail($response->msg);
			return view('purchase_supplier.statistics')->with('viewModel', $this->_viewModel);
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
