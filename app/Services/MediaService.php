<?php

namespace App\Services;

use App\Repositories\MediaRepository;
use App\Enums\FormAction;
use App\Enums\MediaType;
use App\Libraries\ResponseLib;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Fluent;
use Exception;

#當主Service
class MediaService
{
	private $_log	= NULL;
	private $_logChannel = 'apiMediaLog';
	
	public function __construct(protected MediaRepository $_repository)
	{
		$this->_log 	= new Fluent();
	}
	
	/* ====================== List ====================== */
	/* Create medias
	 * @params: clone fluent
	 * @return: array
	 */
	public function list($request = NULL)
	{
		try
		{
			$this->_log->request = $request->toArray();
			
			#1.Get list
			$list = $this->_repository->getList($request);
			
			#2.Build response
			$list = $this->_buildList($list);
			
			#5.Log & return
			$response = ResponseLib::initialize($list)->success()->get();
			
			$this->_log->response = $response;
			Log::channel($this->_logChannel)->info('media.list', $this->_log->toArray());
			
			return $response;
		}
		catch(Exception $e)
		{
			$response = ResponseLib::initialize()->fail($e->getMessage())->get(); 
			
			$this->_log->response = $response;
			Log::channel($this->_logChannel)->error('media.list', $this->_log->toArray());
			
			return $response;
		}
	}
	
	/* Build list
	 * @params: clone fluent
	 * @return: array
	 */
	private function _buildList($list)
	{
		$data = collect($list)->map(function($item, $key){
			
			$item = $this->_buildMetaData($item['_id'], $item['name'], $item['path'], $item['startDate'], $item['endDate'], $item['type'],  $item['enabled']);
			
			return $item;
		})->toArray();
		
		return $data;
	}
	
	/* ====================== Create ====================== */
	/* Create medias
	 * @params: clone fluent
	 * @return: array
	 */
	public function create($request)
	{
		try
		{
			$this->_log->request = $request->toArray();
			
			#1.重整參數
			$this->_initRequest($request);
			
			#2.Save media file
			$this->_processMediaFile($request);
			
			#3.Insert to db
			$request->id = $this->_repository->insert($request);
			
			#4.Build response
			$metaData = $this->_buildMetaData($request->id, $request->mediaName, $request->path, $request->stDate, $request->endDate, $request->type, $request->enabled);
			
			#5.Return response
			$response = ResponseLib::initialize($metaData)->success()->get();
			
			$this->_log->response = $response;
			Log::channel($this->_logChannel)->info('media.create', $this->_log->toArray());
			
			return $response;
		}
		catch(Exception $e)
		{
			$this->_removeMedia($request->id, $request->path);
			
			#Api要call get()直接回傳
			$response = ResponseLib::initialize()->fail($e->getMessage())->get(); 
			
			$this->_log->response = $response;
			Log::channel($this->_logChannel)->error('media.create', $this->_log->toArray());
			
			return $response;
		}
	}
	
	/* ====================== 主流程 End ====================== */
	
	
	/* ====================== Common ====================== */
	/* Init input params for create or update
	 * @params: fluent
	 * @return: fluent
	 */
	private function _initRequest($request)
	{
		$request->id 			= intval($request->id);
		$request->type			= intval($request->type);
		$request->enabled		= boolval($request->enabled);
	}
	
	/* File save for create or update
	 * @params: fluent
	 * @return: array
	 */
	private function _processMediaFile($request)
	{
		#目前只有新增,編輯無
		#extension():mime type / guessClientExtension():client副檔名
		if ($request->type == MediaType::IMAGE->value && $request->uploadFile->isValid())
			$request->path = Storage::disk('tvMenu')->putFile('', $request->uploadFile); #file name, subfolder is empty
		else if ($request->type == MediaType::VIDEO->value)
			$request->path = $request->uploadLink;
		else
			throw new Exception('無法識別媒體類型');
		
		unset($request->uploadFile);
		unset($request->uploadLink);
	}
	
	/* Build response data for return
	 * @params: 
	 * @return: array
	 */
	private function _buildMetaData($id = 0, $mediaName = '', $path = '', $stDate = NULL, $endDate = NULL, $type = 0, $enabled = TRUE)
	{
		#正規化Media output
		$data['id'] 		= intval($id);
		$data['mediaName'] 	= $mediaName;
		$data['mediaUrl']	= ($type == MediaType::IMAGE->value) ? Storage::disk('tvMenu')->url($path) : $path;
		$data['stDate'] 	= $stDate;
		$data['endDate'] 	= $endDate;
		$data['type'] 		= $type;
		$data['typeName'] 	= MediaType::tryFrom($type)->label();
		$data['enabled'] 	= boolval($enabled);
		
		return $data;
	}
	
	/* Remove file
	 * @params: fluent
	 * @return: array
	 */
	private function _removeMedia($id, $fileName)
	{
		if (! empty($id))
			$this->_repository->remove($id);
		
		if (! empty($fileName))
		{
			if (Storage::disk('tvMenu')->exists($fileName))
				Storage::disk('tvMenu')->delete($fileName);
		}
		
		return TRUE;
	}
	
}
