<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\SaleEventsService;
use App\ViewModels\SaleEventsViewModel;
use App\Enums\Functions;
use App\Enums\FormAction;
use App\Enums\Brand;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

/*銷售活動統計*/
class SaleEventsController extends Controller
{
	public function __construct(protected SaleEventsService $_service, protected SaleEventsViewModel $_viewModel)
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
		
		return view('sale_events.statistics')->with('viewModel', $this->_viewModel);
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
		$searchStDate		= $request->input('searchStDate');
		$searchEndDate		= $request->input('searchEndDate');
		$searchAreaIds		= $request->array('searchAreaIds');
		
		$this->_viewModel->initialize($brand, $function);
		$this->_viewModel->keepSearchData($searchType, $searchStDate, $searchEndDate, $searchAreaIds);
		
		$response = $this->_service->getStatistics($brand, $searchType, $searchStDate, $searchEndDate, $searchAreaIds);
		
		if ($response->status === FALSE)
			$this->_viewModel->fail($response->msg);
		else
			$this->_viewModel->success();
		
		$this->_viewModel->statistics = $response->data; #失敗也要有預設值
		
		return view('sale_events.statistics')->with('viewModel', $this->_viewModel);
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
			return view('sale_events.statistics')->with('viewModel', $this->_viewModel);
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
