<?php

namespace App\Services;

use App\Repositories\MenuRepository;
use App\Enums\MediaType;
use App\Libraries\ResponseLib;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Fluent;
use Exception;

class MenuService
{
	private $_log	= NULL;
	private $_logChannel = 'apiMenuLog';
	
	public function __construct(protected MenuRepository $_repository)
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
			
			#3.Log & return
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
			
			$item = $this->_buildMetaData($item['_id'], $item['menuName'], $item['isDefault']);
			
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
			dd($this->_log);
			#1.Save media file
			$this->_processMediaFile($request);
			
			#2.Insert to db
			$request->id = $this->_repository->insert($request);
			
			#3.Build response
			$metaData = $this->_buildMetaData($request->id, $request->mediaName, $request->path, $request->stDate, $request->endDate, $request->type, $request->enabled);
			
			#4.Return response
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
	
	
	/* ====================== Get media ====================== */
	/* Get medias by id
	 * @params: int
	 * @return: array
	 */
	public function getMedia($id)
	{
		try
		{
			$this->_log->request = $id;
			
			#1.get media
			$media = $this->_repository->getById($id);
			
			#2.Build response
			$metaData = $this->_buildMetaData($media['_id'], $media['name'], $media['path'], $media['startDate'], $media['endDate'], $media['type'],  $media['enabled']);
			
			#3.Return response
			$response = ResponseLib::initialize($metaData)->success()->get();
			
			$this->_log->response = $response;
			Log::channel($this->_logChannel)->info('media.detail', $this->_log->toArray());
			
			return $response;
		}
		catch(Exception $e)
		{
			#Api要call get()直接回傳
			$response = ResponseLib::initialize()->fail($e->getMessage())->get(); 
			
			$this->_log->response = $response;
			Log::channel($this->_logChannel)->error('media.detail', $this->_log->toArray());
			
			return $response;
		}
	}
	
	/* Update medias
	 * @params: clone fluent
	 * @return: array
	 */
	public function update($request)
	{
		try
		{
			$this->_log->request = $request->toArray();
			
			#1.Insert to db
			$this->_repository->update($request);
			
			#2.重取Data
			$media = $this->_repository->getById($request->id);
			
			#3.Build response
			$metaData = $this->_buildMetaData($media['_id'], $media['name'], $media['path'], $media['startDate'], $media['endDate'], $media['type'],  $media['enabled']);
			
			#5.Return response
			$response = ResponseLib::initialize($metaData)->success()->get();
			
			$this->_log->response = $response;
			Log::channel($this->_logChannel)->info('media.update', $this->_log->toArray());
			
			return $response;
		}
		catch(Exception $e)
		{
			$response = ResponseLib::initialize()->fail($e->getMessage())->get(); 
			
			$this->_log->response = $response;
			Log::channel($this->_logChannel)->error('media.update', $this->_log->toArray());
			
			return $response;
		}
	}
	
	
	/* Delete medias
	 * @params: clone fluent
	 * @return: array
	 */
	public function delete($request)
	{
		try
		{
			$this->_log->request = $request->toArray();
			
			#1.取舊Data
			$media = $this->_repository->getById($request->id);
			
			#2.Delte media
			$this->_repository->remove($request->id);
			
			#3.Remove file
			$id 	= $media['_id'];
			$type 	= $media['type'];
			$path	= $media['path'];
			
			if ($type == MediaType::IMAGE->value)
				$this->_removeMedia($id, $path);
			
			#4.Return response
			$response = ResponseLib::initialize()->success()->get();
			
			$this->_log->response = $response;
			Log::channel($this->_logChannel)->info('media.delete', $this->_log->toArray());
			
			return $response;
		}
		catch(Exception $e)
		{
			$response = ResponseLib::initialize()->fail($e->getMessage())->get(); 
			
			$this->_log->response = $response;
			Log::channel($this->_logChannel)->error('media.delete', $this->_log->toArray());
			
			return $response;
		}
	}
	/* ====================== 主流程 End ====================== */
	
	
	/* ====================== Common ====================== */
	
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
	private function _buildMetaData($id = 0, $name = '', $isDefault = FALSE)
	{
		#正規化Menu output
		$data['id'] 		= intval($id);
		$data['name'] 		= $name;
		$data['isDefault'] 	= boolval($isDefault);
		
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
