<?php

namespace App\Services;

use App\Enums\MediaType;
use App\Repositories\PlayRepository;
use App\Libraries\ResponseLib;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Fluent;
use Exception;

class PlayService
{
	private $_log	= NULL;
	private $_logChannel = 'apiPlayLog';
	
	public function __construct(protected PlayRepository $_repository)
	{
		$this->_log = new Fluent();
	}
	
	/* ====================== List ====================== */
	/* Get store from ezorder
	 * @params: fluent
	 * @return: array
	 */
	public function getTVMenu($request = NULL)
	{
		try
		{
			$this->_log->request = $request->toArray();
			
			#1.Get menu by store
			$menu = $this->_getStoreMenuOrDefault($request->storeId, $request->tvId);
			
			#2.Build data for response
			$menu = $this->_buildData($request->storeId, $request->tvId, $menu);
			
			#3.Log & return
			$response = ResponseLib::initialize($menu)->success()->get();
			
			$this->_log->response = $response;
			Log::channel($this->_logChannel)->info('tvMenu.play', $this->_log->toArray());
			
			return $response;
		}
		catch(Exception $e)
		{
			$response = ResponseLib::initialize()->fail('讀取TV Menu失敗')->get(); 
			
			$this->_log->response = $response;
			Log::channel($this->_logChannel)->error('tvMenu.play', $this->_log->toArray());
			Log::channel($this->_logChannel)->error('tvMenu.play[exception]', [$e->getMessage()]);
			
			return $response;
		}
	}
	
	/* Get store menu or default
	 * @params: string
	 * @params: int
	 * @return: array
	 */
	private function _getStoreMenuOrDefault($storeId, $tvId)
	{
		/* array: [
			"menuName" => "new menu 2"
			"isDefault" => "0"
			"duration" => "5"
			"sort" => "2"
			"mediaName" => "webp-003"
			"path" => "475388b5-cb6e-4116-a55f-6be5ea945ec5.jpg"
			"type" => "1"
		] */
  
		#無值時取Default Menu
		$menu = $this->_repository->getStoreMenuByTvId($storeId, $tvId);
		
		if (empty($menu))
			$menu = $this->_repository->getStoreMenuByDefault();
		
		return $menu;
	}
	
	/* Format output data
	 * @params: array
	 * @return: array
	 */
	private function _buildData($storeId, $tvId, $baseMenu)
	{
		#DB已過濾日期及Enabled
		$baseData = collect($baseMenu);
		
		$menu['storeId'] 	= $storeId;
		$menu['tvId'] 		= intval($tvId);
		$menu['menuName'] 	= $baseData->pluck('menuName')->first();
		$menu['isDefault'] 	= boolval($baseData->pluck('isDefault')->first());
		
		$menu['medais']		= $baseData->sortBy('sort')->map(function($item, $key){
			$temp['name'] 		= $item['mediaName'];
			$temp['type'] 		= intval($item['type']);
			
			if ($temp['type'] == MediaType::IMAGE->value)
			{
				$temp['mediaPath']	= Storage::disk('tvMenu')->url($item['path']);
				$temp['webpPath']	= Storage::disk('tvMenu')->url("webp/{$item['path']}");
				
			}
			else
			{
				$temp['mediaPath']	= $item['path'];
				$temp['webpPath']	= FALSE;
			}	
			
			$temp['duration'] 	= intval($item['duration']);
			$temp['sort'] 		= intval($item['sort']);
			
			return $temp;
		})->values()->toArray();
		
		return $menu;
	}
	/* ====================== 主流程 End ====================== */
	
	
}
