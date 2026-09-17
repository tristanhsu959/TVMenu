<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\AreaManagerService;
use App\ViewModels\AreaManagerViewModel;
use App\Enums\Brand;
use App\Enums\FormAction;
use App\Enums\Functions;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AreaManagerController extends Controller
{
	public function __construct(protected AreaManagerService $_service, protected AreaManagerViewModel $_viewModel)
	{
	}
	
	public function showSearch(Request $request)
	{
		$this->_viewModel->initialize(FormAction::LIST);
		$this->_viewModel->keepSearchData();
		
		return view('area_manager.index')->with('viewModel', $this->_viewModel);
	}
	
	/* Search
	 * @params: request
	 * @return: view
	 */
	public function search(Request $request)
	{
		#query params
		$searchBrandId	= $request->input('searchBrandId');
		$searchAreaIds	= $request->array('searchAreaIds');
		
		$this->_viewModel->initialize(FormAction::LIST);
		$this->_viewModel->keepSearchData($searchBrandId, $searchAreaIds);
		
		$response = $this->_service->getTemplate($searchBrandId, $searchAreaIds);
		
		if ($response->status === FALSE)
			$this->_viewModel->fail($response->msg);
		else
			$this->_viewModel->success();
		
		$this->_viewModel->list = $response->data;
		
		return view('area_manager.index')->with('viewModel', $this->_viewModel);
	}
	
	/* Update
	 * @params: request
	 * @return: view
	 */
	public function update(Request $request)
	{
		$uploadFile	= $request->file('uploadFile');
		
		$this->_viewModel->initialize(FormAction::LIST);
		$this->_viewModel->keepSearchData();
		
		$validator = Validator::make($request->all(), [
            'uploadFile' => 'required|file|mimes:xlsx|max:10240',
        ]);
 
        if ($validator->fails()) 
		{
			$this->_viewModel->fail('上傳檔案格式錯誤或無法辨識');
			return view('area_manager/index')->with('viewModel', $this->_viewModel);
		}
		
		$response = $this->_service->update($uploadFile);
		
		if ($response->status === FALSE)
			$this->_viewModel->fail($response->msg);
		else
			$this->_viewModel->success($response->msg);
		
		return view('area_manager.index')->with('viewModel', $this->_viewModel);
	}
	
	/* Export
	 * @params: request
	 * @return: view
	 */
	public function export(Request $request, $token)
	{
		$this->_viewModel->initialize(FormAction::LIST);
		$this->_viewModel->keepSearchData();
		
		$response = $this->_service->export($token);
		
		if ($response->status === FALSE)
		{
			$this->_viewModel->fail($response->msg);
			return view('area_manager.index')->with('viewModel', $this->_viewModel);
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
