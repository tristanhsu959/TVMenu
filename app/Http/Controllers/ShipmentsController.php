<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\ShipmentsService;
use App\ViewModels\ShipmentsViewModel;
use App\Enums\Brand;
use App\Enums\FormAction;
use App\Enums\Functions;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ShipmentsController extends Controller
{
	public function __construct(protected ShipmentsService $_service, protected ShipmentsViewModel $_viewModel)
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
		
		return view('shipments.statistics')->with('viewModel', $this->_viewModel);
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
		$searchBy 			= $request->input('searchBy'); #門店/工廠
		$searchCalc 		= $request->input('searchCalc');
		$searchStDate 		= $request->input('searchStDate');
		$searchEndDate 		= $request->input('searchEndDate');
		$searchAreaIds 		= $request->array('searchAreaIds');
		$searchWhere 		= $request->input('searchWhere');
		$searchKeyword		= $request->input('searchKeyword');
		$searchCategory 	= $request->input('searchCategory');
		$searchShortCodes 	= $request->array('searchShortCodes');
		$searchStoreName 	= $request->input('searchStoreName');
 		
		$this->_viewModel->initialize($brand, $function);
		$this->_viewModel->keepSearchData($searchType, $searchBy, $searchCalc, $searchStDate, $searchEndDate, 
					$searchAreaIds, $searchWhere, $searchKeyword, $searchCategory, $searchShortCodes, $searchStoreName); 
		
		#validate input
		$validator = Validator::make($request->all(), [
			'searchStDate' 	=> 'required|date_format:Y-m-d',
			'searchEndDate'	=> 'required|date_format:Y-m-d',
        ]);
 
        if ($validator->fails()) 
		{
			$this->_viewModel->fail('查詢參數錯誤');
			return view('shipments.statistics')->with('viewModel', $this->_viewModel);
		}
		
		$response = $this->_service->getStatistics($brand, $searchType, $searchBy, $searchCalc, $searchStDate, $searchEndDate, 
						$searchAreaIds, $searchWhere, $searchKeyword, $searchCategory, $searchShortCodes, $searchStoreName);
		
		if ($response->status === FALSE)
			$this->_viewModel->fail($response->msg);
		else
			$this->_viewModel->success();
		
		$this->_viewModel->statistics = $response->data; 
		
		return view('shipments.statistics')->with('viewModel', $this->_viewModel); 
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
			return view('shipments.statistics')->with('viewModel', $this->_viewModel);
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
