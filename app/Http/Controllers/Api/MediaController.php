<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MediaService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Fluent;

class MediaController extends Controller
{
	public function __construct(protected MediaService $_service)
	{
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
