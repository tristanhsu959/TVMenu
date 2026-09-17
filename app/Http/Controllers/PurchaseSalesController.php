<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\PurchaseSalesService;
use App\ViewModels\PurchaseSalesViewModel;
use App\Enums\Brand;
use App\Enums\FormAction;
use App\Enums\Functions;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

class PurchaseSalesController extends Controller
{
	public function __construct(protected PurchaseSalesService $_service, protected PurchaseSalesViewModel $_viewModel)
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
		
		return view('purchase_sales.index')->with('viewModel', $this->_viewModel);
	}
	
	/* Search
	 * @params: request
	 * @return: view
	 */
	public function search(Request $request)
	{
		$brand 		= $this->_service->parsingBrand($request->segments());
		$function 	= $this->_service->parsingFunction($brand);
		
		if ($request->isMethod('post'))
		{
			#query params
			$searchType		= $request->input('searchType');
			$searchDate		= $request->input('searchDate');
			$searchAreaId	= $request->input('searchAreaId');
			$searchStoreName= $request->input('searchStoreName');
			
			$this->_viewModel->initialize($brand, $function);
			$this->_viewModel->keepSearchData($searchType, $searchDate, $searchAreaId, $searchStoreName);
			
			$response = $this->_service->getStoreList($brand, $searchType, $searchDate, $searchAreaId, $searchStoreName);
			
			if ($response->status === FALSE)
				$this->_viewModel->fail($response->msg);
			else
				$this->_viewModel->success();
		}
		else
		{
			$response = $this->_service->getLastSearchData($function);
			$params = $response->data;
			
			$this->_viewModel->initialize($brand, $function);
			$this->_viewModel->keepSearchData($params['searchType'], $params['searchDate'], $params['searchAreaId'], $params['searchStoreName']);
		}
		
		$this->_viewModel->statistics = $response->data; #失敗也要有預設值
		
		return view('purchase_sales.index')->with('viewModel', $this->_viewModel);
	}
	
	/* Search
	 * @params: request
	 * @return: view
	 */
	public function detail(Request $request)
	{
		$brand 		= $this->_service->parsingBrand($request->segments());
		$function 	= $this->_service->parsingFunction($brand);
		
		#query params
		$searchStoreId		= $request->input('searchStoreId');
		$searchDate			= $request->input('searchDate');
		
		$this->_viewModel->initialize($brand, $function, FormAction::DETAIL);
		
		$backRoute = Str::replace('?', $brand->code(), '?.purchase_sales.list');
		
		if (empty($searchStoreId))
			return redirect()->route($backRoute)->with('msg', '無法識別門店代碼');
		
		$response = $this->_service->getStatistics($brand, $searchDate, $searchStoreId);
		
		if ($response->status === FALSE)
			return redirect()->route($backRoute)->with('msg', $response->msg);
		
		$this->_viewModel->success();
		$this->_viewModel->statistics = $response->data; #失敗也要有預設值
		
		return view('purchase_sales.detail')->with('viewModel', $this->_viewModel);
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
			return view('purchase_sales.index')->with('viewModel', $this->_viewModel);
		}
		else
		{
			$fileName = $response->data; #成功data會存入檔名
			$filePath = Storage::disk('export')->path($fileName);
			
			if (file_exists($filePath)) {
				return response()->download($filePath)->deleteFileAfterSend();
			}
			#return Storage::disk('export')->download($fileName)->deleteFileAfterSend();
			#return response()->download($fileName);
		}
	}
}
