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
	private $_data	= NULL;
	private $_log	= NULL;
	private $_logChannel = 'apiMediaLog';
	
	public function __construct(protected MediaRepository $_repository)
	{
		$this->_data 	= new Fluent();
		$this->_log 	= new Fluent();
	}
	
	/* ====================== 主流程 ====================== */
	/* Create medias
	 * @params: clone fluent
	 * @return: array
	 */
	public function create($formData)
	{
		try
		{
			$this->_log->request = $formData->toArray();
			
			#1.重整參數
			$this->_initData(FormAction::CREATE->value, $formData);
			
			#2.Save media file
			$this->_processMediaFile(FormAction::CREATE->value);
			
			#3.Save media file
			$this->_insertData();
			
			#4.Build response
			$this->_buildResponseData();
			
			#5.Log & return
			$response = ResponseLib::initialize($this->_data->toArray())->success()->get();
			
			$this->_log->response = $response;
			Log::channel($this->_logChannel)->info('media.create', $this->_log->toArray());
			
			return $response;
		}
		catch(Exception $e)
		{
			$this->_removeMediaFile();
			
			#Api要call get()直接回傳
			$response = ResponseLib::initialize($this->_data->toArray())->fail($e->getMessage())->get(); 
			
			$this->_log->response = $response;
			Log::channel($this->_logChannel)->error('media.create', $this->_log->toArray());
			
			return $response;
		}
	}
	
	/* ====================== 主流程 End ====================== */
	
	
	/* ====================== Common ====================== */
	/* Init input params
	 * @params: fluent
	 * @return: fluent
	 */
	private function _initData($action, $formData)
	{
		#正規化參數
		$this->_data->id = 0; #default
		
		if ($action == FormAction::UPDATE->value OR $action == FormAction::DELETE->value)
			$this->_data->id = intval($formData->id);
		
		$this->_data->mediaName 	= $formData->mediaName;
		$this->_data->uploadFile 	= $formData->uploadFile;
		$this->_data->uploadLink 	= $formData->uploadLink;
		$this->_data->path 			= ''; #fileName
		$this->_data->mediaUrl 		= '';
		$this->_data->stDate 		= $formData->stDate;
		$this->_data->endDate 		= $formData->endDate;
		$this->_data->type			= intval($formData->type);
		$this->_data->enabled		= boolval($formData->enabled);
	}
	
	/* File save for create or update
	 * @params: fluent
	 * @return: array
	 */
	private function _processMediaFile($action)
	{
		#extension():mime type / guessClientExtension():client副檔名
		if ($this->_data->type == MediaType::IMAGE->value && $this->_data->uploadFile->isValid())
			$this->_data->path = Storage::disk('tvMenu')->putFile('', $this->_data->uploadFile); #file name, subfolder is empty
		else if ($this->_data->type == MediaType::VIDEO->value)
			$this->_data->path = $this->_data->uploadLink;
		else
			throw new Exception('無法識別媒體類型');
		
		unset($this->_data->uploadFile);
		unset($this->_data->uploadLink);
	}
	
	/* File save
	 * @params: fluent
	 * @return: array
	 */
	private function _insertData()
	{
		$insertId = $this->_repository->insert($this->_data);
		
		$this->_data->id = $insertId;
	}
	
	/* Build response data for return
	 * @params: fluent
	 * @return: array
	 */
	private function _buildResponseData()
	{
		if ($this->_data->type == MediaType::IMAGE->value)
			$this->_data->mediaUrl = Storage::disk('tvMenu')->url($this->_data->path);
		else if ($this->_data->type == MediaType::VIDEO->value)
			$this->_data->mediaUrl = $this->_data->path;
		
		$this->_data->typeName 	= MediaType::tryFrom($this->_data->type)->label();
		
		unset($this->_data->path);
	}
	
	/* Remove file
	 * @params: fluent
	 * @return: array
	 */
	private function _removeMediaFile($fileName)
	{
		if (empty($fileName))
			return TRUE;
		
		if (Storage::disk('tvMenu')->exists($fileName))
			Storage::disk('tvMenu')->delete($fileName);
		
		return TRUE;
	}
	
}
