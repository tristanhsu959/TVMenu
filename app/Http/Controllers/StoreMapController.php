<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\StoreMapService;
use App\ViewModels\StoreMapViewModel;
use App\Enums\FormAction;
use App\Enums\Functions;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

class StoreMapController extends Controller
{
	public function __construct(protected StoreMapService $_service, protected StoreMapViewModel $_viewModel)
	{
	}
	
	/* Search
	 * @params: request
	 * @return: view
	 */
	public function list(Request $request)
	{
		#query params
		$searchType		= $request->input('searchType');
		$searchStDate	= $request->input('searchStDate');
		
		$this->_viewModel->initialize($brand, $function);
		$this->_viewModel->keepSearchData($searchType, $searchStDate);
		
		$response = $this->_service->getStatistics($brand, $searchType, $searchStDate);
		
		if ($response->status === FALSE)
			$this->_viewModel->fail($response->msg);
		else
			$this->_viewModel->success();
		
		$this->_viewModel->statistics = $response->data; #失敗也要有預設值
		
		return view('merchant.index')->with('viewModel', $this->_viewModel);
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
			return view('merchant.index')->with('viewModel', $this->_viewModel);
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
