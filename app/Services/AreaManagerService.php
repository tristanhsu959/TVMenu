<?php

namespace App\Services;

use App\Facades\AppManager;
use App\Facades\StoreManager;
use App\Facades\PurchaseManager;
use App\Repositories\AreaManagerRepository;
use App\Libraries\ResponseLib;
use App\Libraries\HelperLib;
use App\Libraries\Purchase\AreaLib;
use App\Enums\Brand;
use App\Enums\Functions;
use App\Enums\Area;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Fluent;
use Carbon\CarbonPeriod;
use Exception;
use OpenSpout\Writer\Common\Creator\WriterEntityFactory;
use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Reader\XLSX\Reader;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;

class AreaManagerService
{
	public function __construct(protected AreaManagerRepository $_repository)
	{
	}
	
	/* Search data
	 * @params: enum
	 * @params: string
	 * @params: date
	 * @return: array
	 */
	public function getTemplate($brandId, $areaIds)
	{
		try
		{
			$brand	= Brand::tryFrom($brandId);
			
			$areaManagers 	= $this->_getAreaManagerMapping(); #因為是local直接全抓
			$storeList 		= $this->_getStoreList($brand, $areaIds, $areaManagers);
			
			$result 		= $this->_saveTemplate($brand, $areaIds, $storeList);
			
			return ResponseLib::initialize($result)->success();
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			return ResponseLib::initialize()->fail('讀取門店清單時發生錯誤');
		}
	}
	
	/* Get area manager current setting
	 * @params: 
	 * @return: array
	 */
	private function _getAreaManagerMapping()
	{
		/*[
			1000001 => "陳奕璇",....
		]
		*/
		
		$managers = $this->_repository->getAreaManagerMapping();
		
		$mappings = collect($managers)->mapWithKeys(function($item, $key){
			return [$item['storeKey'] => $item['areaManager']];
		})->all();
		
		return $mappings;
	}
	
	/* Search data
	 * @params: enum
	 * @params: string
	 * @params: date
	 * @return: array
	 */
	private function _getStoreList($brand, $areaIds, $areaManagers = [])
	{
		/* "brandNo" => "BF"
		"areaId" => "24"
		"storeNo" => "KH6000001"
		"storeName" => "嘉義民國店"
		"posId" => "0633"
		"storePhone" => "05-274-5353"
		"address" => "嘉義市東區公明路84號"
		"vatNumber" => "98862754"
		"bossName" => "劉俊良"
		"bossPhone" => "0905-370-720"
		"closeDate" => null
		*/
		
		#此功能不分區, 但取門店需要此參數
		$allowOpCenterIds	= PurchaseManager::getAllowOpCenters($brand); #只有取門店需要,無需代參數
		$allowAreaIds		= PurchaseManager::getAllowAreas($areaIds); #整併查詢參數
		
		$storeList = $this->_repository->getStoreList($brand, $allowOpCenterIds, $allowAreaIds);
		$storeList = StoreManager::filterActiveStoreByCloseDate($storeList); #只排除有設閉店
		
		#之後要再取area manager設定
		#欄位先取, 視狀況再決定要顯示多少
		#依區分組
		$result = collect($storeList)->map(function($item, $key) use($areaManagers){
			$area = AreaLib::toArea(intval($item['areaId']));
			
			$item['areaId']		= $area->value;
			$item['areaName']	= $area->label();
			$item['storeKey'] 	= StoreManager::buildStoreKey($item['storeNo']);
			$item['areaManager']= data_get($areaManagers, $item['storeKey'], '');
			
			return $item;
		})->sortBy('areaId')->groupBy('areaName')->toArray();
		
		return $result;
	}
	
	
	/* Generate statistics data
	 * @params: object
	 * @return: array
	 */
	private function _saveTemplate($brand, $areaIds, $storeList)
	{
		$result = [];
		
		$result['brandName']	= $brand->label();
		$result['storeList'] 	= $storeList;
		$result['hasResult']	= FALSE;
		$result['exportToken']	= FALSE;
		
		if (! empty($storeList))
		{
			$result['hasResult']	= TRUE;
			
			$functions	= Functions::AREA_MANAGER;
			$cacheKey 	= HelperLib::buildCacheKey([$functions->value, $brand->value, $areaIds]);
			
			$result['exportToken'] 	= bin2hex($cacheKey); #hex2bin
			Cache::put($cacheKey, $result, now()->addMinutes(10));
		}
		
		return $result;
	}
	
	
	
	/* Export data
	 * @params: enum
	 * @params: date
	 * @params: date
	 * @return: array
	 */
	public function export($token)
	{
		$cacheKey = hex2bin($token);
		
		if (! Cache::has($cacheKey))
			return ResponseLib::initialize()->fail('資料已過期，請重新查詢後下載');
		
		$currentUser = AppManager::getCurrentUser();
		Log::channel('appServiceLog')->info(Str::replaceArray('?', [$currentUser->getAvailableName(), $cacheKey], '[?]Export area manager data-?'));
		
		try
		{
			$sourceData = Cache::get($cacheKey);
		
			#Build export data for sheets
			$export = [];
			
			foreach($sourceData['storeList'] as $area => $areaStoreList)
			{
				$export[$area] = $this->_buildExportTemplate($areaStoreList);
			}
			
			#Write export to file
			$brandName 	= $sourceData['brandName'];
			$areaName	= collect($sourceData['storeList'])->keys()->join('_');
			
			$fileName 	= Str::replaceArray('?', [$brandName, $areaName], '?_?_督導更新範本.xlsx');
			$filePath 	= Storage::disk('export')->path($fileName);
			
			$writer = new Writer();
			$writer->openToFile($filePath);
			
			$index = 0;
			foreach($export as $sheetName => $sheetData)
			{
				$sheet = ($index == 0) ? $writer->getCurrentSheet() : $writer->addNewSheetAndMakeItCurrent();
				$sheet->setName($sheetName);
				
				foreach($sheetData as $data)
				{
					$row =  Row::fromValues($data);
					$writer->addRow($row);
				}
				$index++;
			}
			
			$writer->close();
			
			return ResponseLib::initialize($fileName)->success();
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			return ResponseLib::initialize('檔案下載失敗，請重新查詢')->fail();
		}
	}
	
	/* Build data for export
	 * @params: array
	 * @return: array
	 */
	private function _buildExportTemplate($storeList)
	{
		$export = [];
		$export[] = ['區域', '門店代碼', '門店名稱', '加盟主', '督導'];
		
		foreach($storeList as $store)
		{
			$row = [];
			$row[] = $store['areaName'];
			$row[] = $store['storeKey'];
			$row[] = $store['storeName'];
			$row[] = $store['bossName'];
			$row[] = $store['areaManager'];
			
			$export[]= $row;
		}
		
		return $export;
	}
	
	/* Update area manager
	 * @params: object
	 * @return: array
	 */
	public function update($uploadFile)
	{
		try
		{
			$content 	= $this->_getFileContent($uploadFile);
			$updateData = $this->_buildUpdateData($content);
			$result 	= $this->_repository->updateAreaManager($updateData);
			
			$count = count($updateData);
			$msg = "共更新 {$count} 筆資料";
			return ResponseLib::initialize()->success($msg);
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			return ResponseLib::initialize('門店督導更新失敗')->fail();
		}
	}
	
	
	/* Generate statistics data
	 * @params: object
	 * @return: array
	 */
	private function _getFileContent($uploadFile)
	{
		$realPath = $uploadFile->getRealPath();
		
        $reader = new Reader();
        $reader->open($realPath);

        $result = [];
		
		#結果放成同一維array即可
        foreach ($reader->getSheetIterator() as $sheet) 
		{
            foreach ($sheet->getRowIterator() as $index => $row) 
			{
				#排除Header
				if ($index == 1)
					continue;
				
                $rowData = $row->toArray();
                $result[] = $rowData; 
            }
        }
		
		$reader->close();
		
		return $result;
	}
	
	/* Parsing data for update
	 * @params: object
	 * @return: array
	 */
	private function _buildUpdateData($content)
	{
		$colStoreKey 	= 1;
		$colAreaManager = 4;
		
		$data = collect($content)->map(function($item, $key) use($colStoreKey, $colAreaManager){
			
			$temp['storeKey'] 	= data_get($item, $colStoreKey, '');
			$temp['areaManager']= data_get($item, $colAreaManager, ''); 
			
			return $temp;
		})->reject(function($item, $key){
			return empty($item['storeKey']);
		})->values()->all();
		
		return $data;
	}
}
