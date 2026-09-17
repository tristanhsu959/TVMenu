<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\PurchaseNotOrderService;
use App\ViewModels\PurchaseNotOrderViewModel;
use App\Enums\Brand;
use App\Enums\FormAction;
use App\Enums\Functions;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PurchaseNotOrderController extends Controller
{
	public function __construct(protected PurchaseNotOrderService $_service, protected PurchaseNotOrderViewModel $_viewModel)
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
		
		return view('purchase_not_order.statistics')->with('viewModel', $this->_viewModel);
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
		$searchCalc			= $request->input('searchCalc');
		$searchStDate 		= $request->input('searchStDate');
		#$searchEndDate 		= $request->input('searchEndDate');
		$searchAreaIds 		= $request->array('searchAreaIds');
		$searchBy 			= $request->input('searchBy');
		$searchKeyword		= $request->input('searchKeyword');
		$searchCategory 	= $request->input('searchCategory');
		$searchShortCodes 	= $request->array('searchShortCodes');
 		
		$this->_viewModel->initialize($brand, $function);
		$this->_viewModel->keepSearchData($searchType, $searchCalc, $searchStDate, /* $searchEndDate, */ 
					$searchAreaIds, $searchBy, $searchKeyword, $searchCategory, $searchShortCodes); 
		
		#validate input
		$validator = Validator::make($request->all(), [
			'searchStDate' 	=> 'required|date_format:Y-m-d',
			#'searchEndDate'	=> 'required|date_format:Y-m-d',
        ]);
 
        if ($validator->fails()) 
		{
			$this->_viewModel->fail('查詢參數錯誤');
			return view('purchase_not_order.statistics')->with('viewModel', $this->_viewModel);
		}
		
		if ($searchType == 'product' && 
			(($searchBy == 'keyword' && empty($searchKeyword))
				OR ($searchBy == 'category' && empty($searchShortCodes))))
		{
			$this->_viewModel->fail('缺少查詢產品參數');
			return view('purchase_not_order.statistics')->with('viewModel', $this->_viewModel);
		}
		
		$response = $this->_service->getStatistics($brand, $searchType, $searchCalc, $searchStDate, /* $searchEndDate, */ 
						$searchAreaIds, $searchBy, $searchKeyword, $searchCategory, $searchShortCodes);
		
		if ($response->status === FALSE)
			$this->_viewModel->fail($response->msg);
		else
			$this->_viewModel->success();
		
		$this->_viewModel->statistics = $response->data; 
		
		return view('purchase_not_order.statistics')->with('viewModel', $this->_viewModel); 
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
			return view('purchase_not_order.statistics')->with('viewModel', $this->_viewModel);
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
