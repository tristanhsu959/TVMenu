<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MenuService;
use App\Libraries\ResponseLib;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Fluent;
use Illuminate\Support\Facades\Validator;

class MenuController extends Controller
{
	public function __construct(protected MenuService $_service)
	{
	}
	
	/* Get menu list
	 * @params: request
	 * @return: json
	 */
	public function list(Request $request)
	{
		if ($request->ajax())
		{
			#TODO: 參數待確認
			$formData = new Fluent();
			$response = $this->_service->list($formData);
			
			return response()->json($response);
		}
		
		abort(403, '未授權限的呼叫方法');
	}
	
	/* Create menu
	 * @params: request
	 * @return: json
	 */
	public function create(Request $request)
	{
		if ($request->ajax())
		{
			$menuName	= $request->input('menuName');
			$isDefault	= $request->boolean('isDefault', FALSE);
			$medias		= $request->array('medias');
			
			$validator = Validator::make($request->all(), [
				'menuName' => 'required',
				'medias' 	=> 'required|array|min:1',
			]);
			
			if ($validator->fails()) 
			{
				$response = ResponseLib::initialize()->fail('Request參數錯誤或medias值為空')->get();
				return response()->json($response);
			}
			
			$formData = new Fluent([]);
			$formData->menuName($menuName)->isDefault($isDefault)->medias($medias);
						
			#clone避免交叉影響
			$response = $this->_service->create(clone $formData);
			
			return response()->json($response);
		}
		
		abort(403, '未授權限的呼叫方法');
	}
	
	/* Get menu by id
	 * @params: request
	 * @return: json
	 */
	public function detail(Request $request, $id)
	{
		if ($request->ajax())
		{
			if (empty($id)) 
			{
				$response = ResponseLib::initialize()->fail('無法識別 Menu ID')->get();
				return response()->json($response);
			}
			
			$id = intval($id);
			$response = $this->_service->getMenu($id);
			
			return response()->json($response);
		}
		
		abort(403, '未授權限的呼叫方法');
	}
	
	/* Update menu by id
	 * @params: request
	 * @return: json
	 */
	public function update(Request $request, $id)
	{
		#ajax put不能與multipart/form-data共用
		if ($request->ajax())
		{
			if (empty($id)) 
			{
				$response = ResponseLib::initialize()->fail('無法識別 Menu ID')->get();
				return response()->json($response);
			}
			
			$menuName	= $request->input('menuName');
			$isDefault	= $request->boolean('isDefault', FALSE);
			$medias		= $request->array('medias');
			
			$validator = Validator::make($request->all(), [
				'menuName' => 'required',
				'medias' 	=> 'required|array|min:1',
			]);
			
			if ($validator->fails()) 
			{
				$response = ResponseLib::initialize()->fail('Request參數錯誤或medias值為空')->get();
				return response()->json($response);
			}
			
			$formData = new Fluent([]);
			$formData->id(intval($id))->menuName($menuName)->isDefault($isDefault)->medias($medias);
						
			#clone避免交叉影響
			$response = $this->_service->update(clone $formData);
			
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
