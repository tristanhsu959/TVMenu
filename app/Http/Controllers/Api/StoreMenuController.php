<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\StoreMenuService;
use App\Libraries\ResponseLib;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Fluent;
use Illuminate\Support\Facades\Validator;

class StoreMenuController extends Controller
{
	public function __construct(protected StoreMenuService $_service)
	{
	}
	
	/* Get store list
	 * @params: request
	 * @return: json
	 */
	public function list(Request $request, $brand = NULL)
	{
		if ($request->ajax())
		{
			$formData = new Fluent();
			$formData->brand(Str::upper($brand));
			$response = $this->_service->list($formData);
			
			return response()->json($response);
		}
		
		abort(403, '未授權限的呼叫方法');
	}
	
	/* Get store menu by id
	 * @params: request
	 * @return: json
	 */
	public function detail(Request $request, $storeId)
	{
		if ($request->ajax())
		{
			if (empty($storeId)) 
			{
				$response = ResponseLib::initialize()->fail('無法識別 Store ID')->get();
				return response()->json($response);
			}
			
			$response = $this->_service->getMenus($storeId);
			
			return response()->json($response);
		}
		
		abort(403, '未授權限的呼叫方法');
	}
	
	/* Update menu by id
	 * @params: request
	 * @return: json
	 */
	public function upsert(Request $request)
	{
		#ajax put不能與multipart/form-data共用
		if ($request->ajax())
		{
			$storeId	= $request->input('storeId');
			$menus		= $request->array('menus'); #空的也要處理,因有可能要移除
			
			if (empty($storeId)) 
			{
				$response = ResponseLib::initialize()->fail('無法識別 Store ID')->get();
				return response()->json($response);
			}
			
			$formData = new Fluent([]);
			$formData->storeId($storeId)->menus($menus);
						
			#clone避免交叉影響
			$response = $this->_service->upsert(clone $formData);
			
			return response()->json($response);
		}
		
		abort(403, '未授權限的呼叫方法');
	}
	
	/* Delete menu by id
	 * @params: request
	 * @return: json
	 */
	public function delete(Request $request, $id)
	{
		if ($request->ajax())
		{
			if (empty($id)) 
			{
				$response = ResponseLib::initialize()->fail('無法識別 Menu ID')->get();
				return response()->json($response);
			}
			
			$formData = new Fluent([]);
			$formData->id(intval($id));
						
			#clone避免交叉影響
			$response 	= $this->_service->delete(clone $formData);
			
			return response()->json($response);
		}
		
		abort(403, '未授權限的呼叫方法');
	}
}
