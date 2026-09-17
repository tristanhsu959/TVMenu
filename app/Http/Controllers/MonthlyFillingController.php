<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\MonthlyFillingService;
use App\ViewModels\MonthlyFillingViewModel;
use App\Enums\Brand;
use App\Enums\FormAction;
use App\Enums\Functions;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MonthlyFillingController extends Controller
{
	public function __construct(protected MonthlyFillingService $_service, protected MonthlyFillingViewModel $_viewModel)
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
		
		return view('monthly_filling.statistics')->with('viewModel', $this->_viewModel);
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
		$searchRange		= $request->input('searchRange'); #日or月條件
		$searchStDate		= $request->input('searchStDate');
		$searchEndDate		= $request->input('searchEndDate');
		
		$this->_viewModel->initialize($brand, $function);
		$this->_viewModel->keepSearchData($searchStDate, $searchEndDate, $searchType, $searchRange); 
		
		#validate input
		$validator = Validator::make($request->all(), [
			'searchStDate' 	=> 'required',
			'searchEndDate'	=> 'required',
        ]);
		
		#年度不用檢查起迄
        if ($searchRange != 'year' && $validator->fails()) 
		{
			$this->_viewModel->fail('查詢參數錯誤');
			return view('monthly_filling.statistics')->with('viewModel', $this->_viewModel);
		}
		
		$response = $this->_service->getStatistics($brand, $searchStDate, $searchEndDate, $searchType, $searchRange);
		
		if ($response->status === FALSE)
			$this->_viewModel->fail($response->msg);
		else
			$this->_viewModel->success();
		
		$this->_viewModel->statistics = $response->data; 
		
		return view('monthly_filling.statistics')->with('viewModel', $this->_viewModel); 
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
			return view('monthly_filling.statistics')->with('viewModel', $this->_viewModel);
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
