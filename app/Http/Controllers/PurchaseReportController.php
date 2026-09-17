<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\PurchaseReportService;
use App\ViewModels\PurchaseReportViewModel;
use App\Enums\Brand;
use App\Enums\FormAction;
use App\Enums\Functions;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PurchaseReportController extends Controller
{
	public function __construct(protected PurchaseReportService $_service, protected PurchaseReportViewModel $_viewModel)
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
		
		return view('purchase_report.statistics')->with('viewModel', $this->_viewModel);
	}
	
	/* Search
	 * @params: request
	 * @return: view
	 */
	public function search(Request $request)
	{
		$brand 		= $this->_service->parsingBrand($request->segments());
		$function 	= $this->_service->parsingFunction($brand);
		
		$searchType			= $request->input('searchType');
		$searchBrand		= $request->input('searchBrand');
		$searchStDate		= $request->input('searchStDate');
		$searchEndDate		= $request->input('searchEndDate');
		$searchOpCenterIds	= $request->array('searchOpCenterIds');
		$searchAreaIds		= $request->array('searchAreaIds');
		$searchProductCodes	= $request->array('searchProductCodes'); #目前尚未用到
		
		$this->_viewModel->initialize($brand, $function);
		$this->_viewModel->keepSearchData($searchType, $searchBrand, $searchStDate, $searchEndDate, $searchOpCenterIds, $searchAreaIds, $searchProductCodes); 
		
		#validate input
		$validator = Validator::make($request->all(), [
			'searchStDate' 	=> 'required',
			'searchEndDate'	=> 'required',
        ]);
		
		$response = $this->_service->getStatistics($brand, $searchType, $searchBrand, $searchStDate, $searchEndDate, $searchOpCenterIds, $searchAreaIds, $searchProductCodes);
		
		if ($response->status === FALSE)
			$this->_viewModel->fail($response->msg);
		else
			$this->_viewModel->success();
		
		$this->_viewModel->statistics = $response->data; 
		
		return view('purchase_report.statistics')->with('viewModel', $this->_viewModel); 
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
			return view('purchase_report.statistics')->with('viewModel', $this->_viewModel);
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
