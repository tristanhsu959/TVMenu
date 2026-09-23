<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MediaService;
use App\Libraries\ResponseLib;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Fluent;
use Illuminate\Support\Facades\Validator;

class MediaController extends Controller
{
	public function __construct(protected MediaService $_service)
	{
	}
	
	/* Get media list
	 * @params: request
	 * @return: json
	 */
	public function list(Request $request)
	{
		if ($request->ajax())
		{
			#尚未定義
			#$enabled	= $request->input('enabled');
			
			$formData = new Fluent([]);
			$response = $this->_service->list($formData);
			
			return response()->json($response);
		}
		
		abort(403, '未授權限的呼叫方法');
	}
	
	/* Create image or video into media library
	 * @params: request
	 * @return: json
	 */
	public function create(Request $request)
	{
		if ($request->ajax())
		{
			$mediaName	= $request->input('mediaName');
			$uploadFile	= $request->file('uploadFile');
			$uploadLink	= $request->input('uploadLink'); #or Month
			$stDate		= $request->input('stDate');
			$endDate	= $request->input('endDate');
			$type		= $request->input('type');
			$enabled	= $request->boolean('enabled');
			
			$validator = Validator::make($request->all(), [
				'mediaName' => 'required',
				'type' 		=> 'required|in:1,2',
				'enabled' 	=> 'required',
				'uploadFile'=> 'required_if:type,1',
				'uploadLink'=> 'required_if:type,2',
			]);
			
			if ($validator->fails()) 
			{
				$response = ResponseLib::initialize()->fail('Request參數錯誤')->get();
				return response()->json($response);
			}
			
			$formData = new Fluent([]);
			$formData->mediaName($mediaName)->uploadFile($uploadFile)->uploadLink($uploadLink)
						->stDate($stDate)->endDate($endDate)->type($type)->enabled($enabled);
						
			#clone避免交叉影響
			$response 	= $this->_service->create(clone $formData);
			
			return response()->json($response);
		}
		
		abort(403, '未授權限的呼叫方法');
	}
	
}
