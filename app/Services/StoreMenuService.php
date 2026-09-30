<?php

namespace App\Services;

use App\Facades\StoreManager;
use App\Repositories\StoreMenuRepository;
use App\Enums\Brand;
use App\Enums\MediaType;
use App\Libraries\ResponseLib;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Fluent;
use Exception;

class StoreMenuService
{
	private $_log	= NULL;
	private $_logChannel = 'apiStoreMenuLog';
	
	public function __construct(protected StoreMenuRepository $_repository)
	{
		$this->_log 	= new Fluent();
	}
	
	/* ====================== List ====================== */
	/* Get store from ezorder
	 * @params: clone fluent
	 * @return: array
	 */
	public function list($request = NULL)
	{
		try
		{
			$this->_log->request = $request->toArray();
			
			#1.Get list-統一由store manager取得
			$list = StoreManager::getList($request->brand);
			
			#2.Get TV count
			$tvList = $this->_getTVCountByStore();
			
			#3.Build response
			$list = $this->_buildList($list, $tvList);
			
			#4.Log & return
			$response = ResponseLib::initialize($list)->success()->get();
			
			$this->_log->response = $response;
			Log::channel($this->_logChannel)->info('storeMenu.list', $this->_log->toArray());
			
			return $response;
		}
		catch(Exception $e)
		{
			$response = ResponseLib::initialize()->fail('讀取門店清單失敗')->get(); 
			
			$this->_log->response = $response;
			Log::channel($this->_logChannel)->error('storeMenu.list', $this->_log->toArray());
			Log::channel($this->_logChannel)->error('storeMenu.list[exception]', [$e->getMessage()]);
			
			return $response;
		}
	}
	
	/* Get tv count
	 * @params: 
	 * @return: array
	 */
	private function _getTVCountByStore()
	{
		$list = $this->_repository->getTvCount();
		
		$list = collect($list)->mapWithKeys(function($item, $key){
			return [$item['storeId'] => intval($item['tvCount'])];
		})->toArray();
		
		return $list;
	}
	
	/* Build list
	 * @params: clone fluent
	 * @return: array
	 */
	private function _buildList($list, $tvList)
	{
		$list = collect($list)->map(function($item, $key) use($tvList){
			
			$count = data_get($tvList, $item['id'], 0);
			
			$item['tvCount'] = $count;
			
			return $item;
		})->toArray();
		
		return $list;
	}
	
	/* ====================== Create ====================== */
	/* Create menu
	 * @params: clone fluent
	 * @return: array
	 */
	public function create($request)
	{
		try
		{
			$this->_log->request = $request->toArray();
			
			#1.Check default setting
			$this->_resetDefault($request);
			
			#2.Insert to db
			$request->id = $this->_repository->insert($request);
			
			#3.Return response
			$response = ResponseLib::initialize()->success()->get();
			
			$this->_log->response = $response;
			Log::channel($this->_logChannel)->info('storeMenu.create', $this->_log->toArray());
			
			return $response;
		}
		catch(Exception $e)
		{
			#Api要call get()直接回傳
			$response = ResponseLib::initialize()->fail('新增Menu失敗')->get(); 
			
			$this->_log->response = $response;
			
			Log::channel($this->_logChannel)->error('storeMenu.create', $this->_log->toArray());
			Log::channel($this->_logChannel)->error('storeMenu.create[exception]', [$e->getMessage()]);
			
			return $response;
		}
	}
	
	
	/* ====================== Get menu ====================== */
	/* Get menu by id
	 * @params: int
	 * @return: array
	 */
	public function getMenu($id)
	{
		try
		{
			$this->_log->request = $id;
			
			#1.get menu & detail
			$menu = $this->_repository->getById($id);
			
			#2.Build response
			$output = $this->_buildMenu($menu);
			
			#3.Return response
			$response = ResponseLib::initialize($output)->success()->get();
			
			$this->_log->response = $response;
			Log::channel($this->_logChannel)->info('storeMenu.detail', $this->_log->toArray());
			
			return $response;
		}
		catch(Exception $e)
		{
			$response = ResponseLib::initialize()->fail('讀取Menu設定失敗')->get(); 
			
			$this->_log->response = $response;
			Log::channel($this->_logChannel)->error('storeMenu.detail', $this->_log->toArray());
			Log::channel($this->_logChannel)->error('storeMenu.detail[exception]', [$e->getMessage()]);
			
			return $response;
		}
	}
	
	/* Build response data for return
	 * @params: 
	 * @return: array
	 */
	private function _buildMenu($menu)
	{
		/* id: integer,
		menuName: string,
		isDefault: boolean,
		medias:[
			{
				id: integer,
				duration: integer,
				sort: integer
			}, ......
		] */
		$menu = collect($menu);
		
		$output['id'] 			= $menu->pluck('_id')->first();
		$output['menuName'] 	= $menu->pluck('menuName')->first();
		$output['isDefault'] 	= boolval($menu->pluck('isDefault')->first());
		
		
		$medias = $menu->map(function($item, $key){
			$temp['id'] 		= intval($item['mediaId']);
			$temp['duration'] 	= intval($item['duration']);
			$temp['sort'] 		= intval($item['sort']);
			
			return $temp;
		})->toArray();
		
		$output['medias'] = $medias;
		
		return $output;
	}
	
	/* Update menu
	 * @params: clone fluent
	 * @return: array
	 */
	public function update($request)
	{
		try
		{
			$this->_log->request = $request->toArray();
			
			#1.get menu & detail
			$this->_repository->update($request);
			
			#2.Return response
			$response = ResponseLib::initialize()->success()->get();
			
			$this->_log->response = $response;
			Log::channel($this->_logChannel)->info('menu.update', $this->_log->toArray());
			
			return $response;
		}
		catch(Exception $e)
		{
			$response = ResponseLib::initialize()->fail('編輯Menu失敗')->get(); 
			
			$this->_log->response = $response;
			Log::channel($this->_logChannel)->error('storeMenu.update', $this->_log->toArray());
			Log::channel($this->_logChannel)->error('storeMenu.update[exception]', [$e->getMessage()]);
			
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
			$this->_log->request = $request->id;
			
			#1.Delte menu
			$this->_repository->remove($request->id);
			
			#2.Return response
			$response = ResponseLib::initialize()->success()->get();
			
			$this->_log->response = $response;
			Log::channel($this->_logChannel)->info('storeMenu.delete', $this->_log->toArray());
			
			return $response;
		}
		catch(Exception $e)
		{
			$response = ResponseLib::initialize()->fail('刪除Menu失敗')->get(); 
			
			$this->_log->response = $response;
			Log::channel($this->_logChannel)->error('storeMenu.delete', $this->_log->toArray());
			Log::channel($this->_logChannel)->error('storeMenu.delete[exception]', [$e->getMessage()]);
			
			return $response;
		}
	}
	/* ====================== 主流程 End ====================== */
	
	
	/* ====================== Common ====================== */
	
	/* Reset menu default setting
	 * @params: fluent
	 * @return: array
	 */
	private function _resetDefault($request)
	{
		#有設定Default時
		if ($request->isDefault == True)
			$this->_repository->resetDefault();
		
		return TRUE;
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
