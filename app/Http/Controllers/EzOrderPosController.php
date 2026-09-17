<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\EzOrderPosService;
use App\ViewModels\EzOrderPosViewModel;
use App\Enums\Brand;
use App\Enums\FormAction;
use App\Enums\Functions;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

class EzOrderPosController extends Controller
{
	public function __construct(protected EzOrderPosService $_service, protected EzOrderPosViewModel $_viewModel)
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
		
		return view('ezorder_pos.statistics')->with('viewModel', $this->_viewModel);
	}
	
	/* Search
	 * @params: request
	 * @return: view
	 */
	public function search(Request $request)
	{
		$brand 		= $this->_service->parsingBrand($request->segments());
		$function 	= $this->_service->parsingFunction($brand);
		
		#query params
		$searchType			= $request->input('searchType');
		$searchBy			= $request->input('searchBy');
		$searchStDate		= $request->input('searchStDate'); #or Month
		$searchEndDate		= $request->input('searchEndDate');
		$searchAreaIds		= $request->array('searchAreaIds');
		$searchStoreName	= $request->input('searchStoreName', NULL);
		
		$this->_viewModel->initialize($brand, $function);
		$this->_viewModel->keepSearchData($searchType, $searchBy, $searchStDate, $searchEndDate, $searchAreaIds, $searchStoreName);
		
		$response = $this->_service->getStatistics($brand, $searchType, $searchBy, $searchStDate, $searchEndDate, $searchAreaIds, $searchStoreName);
		
		if ($response->status === FALSE)
			$this->_viewModel->fail($response->msg);
		else
			$this->_viewModel->success();
		
		$this->_viewModel->statistics = $response->data; #失敗也要有預設值
		
		return view('ezorder_pos.statistics')->with('viewModel', $this->_viewModel);
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
			return view('ezorder_pos.statistics')->with('viewModel', $this->_viewModel);
		}
		else
		{
			$fileName = $response->data; 
			$filePath = Storage::disk('export')->path($fileName);
			
			if (file_exists($filePath)) {
				return response()->download($filePath)->deleteFileAfterSend();
			}
			#return Storage::disk('export')->download($fileName)->deleteFileAfterSend();
			#return response()->download($fileName);
		}
	}
}
