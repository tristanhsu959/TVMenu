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
	
	
	/* ====================== Get menus ====================== */
	/* Get menu by id
	 * @params: int
	 * @return: array
	 */
	public function getMenus($storeId)
	{
		try
		{
			$this->_log->request = $storeId;
			
			#1.Get store info
			$store = StoreManager::getById($storeId);
			
			#2.get menus & detail
			$store['menus'] = $this->_repository->getById($storeId);
			
			#3.Return response
			$response = ResponseLib::initialize($store)->success()->get();
			
			$this->_log->response = $response;
			Log::channel($this->_logChannel)->info('storeMenu.detail', $this->_log->toArray());
			
			return $response;
		}
		catch(Exception $e)
		{
			$response = ResponseLib::initialize()->fail('讀取Store Menu設定失敗')->get(); 
			
			$this->_log->response = $response;
			Log::channel($this->_logChannel)->error('storeMenu.detail', $this->_log->toArray());
			Log::channel($this->_logChannel)->error('storeMenu.detail[exception]', [$e->getMessage()]);
			
			return $response;
		}
	}
	
	/* Insert or Update store menu
	 * @params: clone fluent
	 * @return: array
	 */
	public function upsert($request)
	{
		try
		{
			$this->_log->request = $request->toArray();
			
			#1.get menu & detail
			$this->_repository->upsert($request->storeId, $request->menus);
			
			#2.Return response
			$response = ResponseLib::initialize()->success()->get();
			
			$this->_log->response = $response;
			Log::channel($this->_logChannel)->info('storeMenu.upsert', $this->_log->toArray());
			
			return $response;
		}
		catch(Exception $e)
		{
			$response = ResponseLib::initialize()->fail('Store Menu設定失敗')->get(); 
			
			$this->_log->response = $response;
			Log::channel($this->_logChannel)->error('storeMenu.upsert', $this->_log->toArray());
			Log::channel($this->_logChannel)->error('storeMenu.upsert[exception]', [$e->getMessage()]);
			
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
			
			#1.Delte menu
			$this->_repository->remove($request->storeId);
			
			#2.Return response
			$response = ResponseLib::initialize()->success()->get();
			
			$this->_log->response = $response;
			Log::channel($this->_logChannel)->info('storeMenu.delete', $this->_log->toArray());
			
			return $response;
		}
		catch(Exception $e)
		{
			$response = ResponseLib::initialize()->fail('Store Menu刪除失敗')->get(); 
			
			$this->_log->response = $response;
			Log::channel($this->_logChannel)->error('storeMenu.delete', $this->_log->toArray());
			Log::channel($this->_logChannel)->error('storeMenu.delete[exception]', [$e->getMessage()]);
			
			return $response;
		}
	}
	/* ====================== 主流程 End ====================== */
	
	
	/* ====================== Common ====================== */
	
	
}
