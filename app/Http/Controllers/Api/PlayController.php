<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PlayService;
use App\Libraries\ResponseLib;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Fluent;
use Illuminate\Support\Facades\Validator;

class PlayController extends Controller
{
	public function __construct(protected PlayService $_service)
	{
	}
	
	/* Get store list
	 * @params: request
	 * @return: json
	 */
	public function play(Request $request, $storeId = NULL, $tvId = NULL)
	{
		if ($request->ajax())
		{
			if (empty($storeId) OR empty($tvId))
			{
				$response = ResponseLib::initialize()->fail('參數錯誤，缺少StoreId|TVId')->get();
				return response()->json($response);
			}
			
			$formData = new Fluent();
			$formData->storeId($storeId);
			$formData->tvId(intval($tvId));
			
			$response = $this->_service->getTVMenu($formData);
			dd(json_encode($response));
			return response()->json($response);
		}
		
		abort(403, '未授權限的呼叫方法');
	}
}
