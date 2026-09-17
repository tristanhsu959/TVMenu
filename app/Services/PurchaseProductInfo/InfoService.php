<?php

namespace App\Services\PurchaseProductInfo;

use App\Facades\AppManager;
use App\Facades\PurchaseManager;
use App\Repositories\PurchaseProductInfoRepository;
use App\Libraries\ResponseLib;
use App\Enums\OpCenter;
use App\Enums\Brand;
use App\Enums\Functions;
use App\Enums\Area;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;
use Carbon\CarbonPeriod;
use Exception;
use OpenSpout\Writer\Common\Creator\WriterEntityFactory;
use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style; 
use OpenSpout\Common\Entity\Style\CellAlignment;

#partial Service
class InfoService
{
	private $_statistics = [];
	
	public function __construct(protected PurchaseProductInfoRepository $_repository)
	{
		$this->_statistics = [
			'type'		=> '',
			'brandId'		=> '', #export
			'brandCode'		=> '',
			'hasOffShelf'	=> FALSE,			
			'product'		=> [],
			'preorder'		=> [],
			'supplier'		=> [],
			'exportName'	=> '', #export
			'exportToken'	=> '', #export
		];
	}
	
	/* ====================== 主流程 By Name ====================== */
	/* Search data
	 * @params: array
	 * @return: array
	 */
	public function analysis($params)
	{
		try
		{
			$this->_prepareData($params);
			
			$this->_outputReport($params);
			
			$this->_generateStatistics($params);
			
			return $this->_statistics;
		}
		catch(Exception $e)
		{
			throw new Exception($e->getMessage());
		}
	}
	
	/* Generate statistics data
	 * @params: array
	 * @return: array
	 */
	private function _generateStatistics($params)
	{
		$this->_statistics['type']			= $params->type;
		$this->_statistics['brandId']		= $params->brand->value; 
		$this->_statistics['brandCode']		= $params->brand->code(); 
		$this->_statistics['product'] 		= $params->product;
		$this->_statistics['preorder'] 		= $params->preorder;
		$this->_statistics['supplier'] 		= $params->supplier;
		$this->_statistics['hasPreorder']	= $params->hasPreorder;
		$this->_statistics['hasSupplier'] 	= $params->hasSupplier;
		$this->_statistics['hasResult'] 	= FALSE;
		
		#無值不cache
		if (! empty($params->product['list']))
		{
			$this->_statistics['hasResult'] 	= TRUE;
			$this->_statistics['exportToken'] 	= bin2hex($params->cacheKey); #hex2bin
			$this->_statistics['exportName'] 	= '訂貨產品清單';
			
			Cache::put($params->cacheKey, $this->_statistics, now()->addMinutes(10));
		}
	}
	
	/* ====================== 主流程 End ====================== */
	
	/* 取統計相關參數
	 * @params: array
	 * @return: array
	 */
	private function _prepareData($params)
	{
		try
		{
			$this->_getProductId($params);
			
			#產品
			$this->_getProductFromDB($params);
			#預購
			$this->_getPreOrderProductFromDB($params);
			#供應商
			$this->_getSupplierProductFromDB($params);
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception($e->getMessage());
		}
	}
	
	/* 以short code取得product id
	 * @params: array
	 * @return: array
	 */
	private function _getProductId($params)
	{
		try
		{
			#取資料都不分營運中心,不然可能會取不到
			$brand 		= $params->brand;
			$opCenterIds= $params->allowOpCenterIds;
			#$areaIds 	= $params->allowAreaIds;
			
			$params->productIds = [];
			$shortCodeIds = [];
			$nameIds = [];
			
			if (empty($params->shortCode) && empty($params->productName))
				return TRUE;
			else if (! empty($params->shortCode) && ! empty($params->productName))
			{
				#都有值, 要取And logic
				$shortCodeIds 	= PurchaseManager::getProductIdByShortCode($brand, $opCenterIds, [$params->shortCode]);
				$nameIds		= PurchaseManager::getProductIdByName($brand, $opCenterIds, $params->productName);
				
				$params->productIds = collect($shortCodeIds)->merge($nameIds)->duplicates()->values()->all();
			}
			
			#兩者之一 
			if (empty($params->shortCode))
				$params->productIds = PurchaseManager::getProductIdByName($brand, $opCenterIds, $params->productName);
			
			if (empty($params->productName))
				$params->productIds = PurchaseManager::getProductIdByShortCode($brand, $opCenterIds, [$params->shortCode]);
			
			if (empty($params->productIds))
				throw new Exception('查無此產品');
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception($e->getMessage());
		}
	}
	
	/* Get order data
	 * @params: array
	 * @return: array
	 */
	private function _getProductFromDB($params)
	{
		/*[
			"opCenter" => "高雄分公司"
			"productName" => "招牌餡"
			"erpNo" => "PR00208001"
			"shortCode" => "0001"
			"memo" => ""
			"price" => "70.00"
			"factoryNo" => "TW_KH"
			"factoryName" => "高雄工廠"
			"warehouseNo" => "W01"
			"warehouse" => "高雄總倉"
			"unit" => "斤"
			"status" => "1"
		]
		*/
	
		try
		{
			$brand 				= $params->brand;
			$allowOpCenterIds 	= $params->allowOpCenterIds;
			$factoryIds 		= $params->factoryIds;
			$hasOffShelf		= $params->hasOffShelf;
			$productIds			= $params->productIds;
			
			$list = $this->_repository->getProductList($brand, $allowOpCenterIds, $factoryIds, $hasOffShelf, $productIds);
			
			$params->productList = $list;
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('讀取訂貨系統訂單資料失敗');
		}
	}
	
	/* 預購
	 * @params: array
	 * @return: array
	 */
	private function _getPreOrderProductFromDB($params)
	{
		/*[
			"productId" => "3226"
			"productName" => "預購什錦麵輪"
			"shortCode" => "0075"
			"memo" => ""
			"status" => "0"
			"unit" => "包"
			"opCenter" => "台北總公司"
		]
		*/
	
		try
		{
			$brand 				= $params->brand;
			$allowOpCenterIds 	= $params->allowOpCenterIds;
			#$factoryIds 		= $params->factoryIds;
			$hasOffShelf		= $params->hasOffShelf;
			#$productIds		= $params->productIds;
			
			#預購無法判別工廠,會有問題
			#預購應是從一般產品加入, 應要有關聯
			if ($params->hasPreorder)
				$list = $this->_repository->getPreOrderProductList($brand, $allowOpCenterIds, $hasOffShelf);
			else
				$list = [];
			
			#因DB有到倉別, 故資料會重複, 要再濾除
			#Id同shortCode不同, 或id,shortCode皆相同
			$params->preorderProductList = collect($list)->map(function($item, $key){
				$item['key'] = "{$item['productId']}:{$item['shortCode']}";
				
				return $item;
			})->groupBy('key')->map(function($items, $key){
				
				$temp['productId']	= $items->pluck('productId')->first();
				$temp['productName']= $items->pluck('productName')->first();
				$temp['shortCode'] 	= $items->pluck('shortCode')->first();
				$temp['memo'] 		= $items->pluck('memo')->first();
				$temp['status'] 	= boolval($items->pluck('status')->first());
				$temp['unit'] 		= $items->pluck('unit')->first();
				$temp['opCenter'] 	= $items->pluck('opCenter')->first();
				
				return $temp;
			})->values()->all();
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('讀取訂貨系統訂單資料失敗');
		}
	}
	
	/* 供應商
	 * @params: array
	 * @return: array
	 */
	private function _getSupplierProductFromDB($params)
	{
		/* [
			"productName" => "福壽耐炸油"
			"erpNo" => "GD00101028"
			"memo" => ""
			"price" => "870.00"
			"purchasePrice" => "820.00"
			"status" => "1"
			"supplierNo" => "MSJS001"
			"supplierName" => "美食家食材"
			"factoryNo" => "TW_TP"
			"factoryName" => "淡水總廠"
			"unit" => "桶"
			"opCenter" => "台北總公司"
		]
		*/
	
		try
		{
			$brand 				= $params->brand;
			$allowOpCenterIds 	= $params->allowOpCenterIds;
			$factoryIds 		= $params->factoryIds;
			$hasOffShelf		= $params->hasOffShelf;
			#$productIds		= $params->productIds;
			
			if ($params->hasSupplier)
				$list = $this->_repository->getSupplierProductList($brand, $allowOpCenterIds, $factoryIds, $hasOffShelf);
			else
				$list = [];
			
			$params->supplierProductList = $list;
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('讀取訂貨系統訂單資料失敗');
		}
	}
	/* ========================== 統計 ========================== */
	/* ========================================================== */
	/* 處理統計資料輸出
	 * @params: array
	 * @return: array
	 */
	private function _outputReport($params)
	{
		try
		{
			#1.一般
			$this->_parsingProduct($params);
			
			#2.預購
			$this->_parsingPreorderProduct($params);
			
			#3.供應
			$this->_parsingSupplierProduct($params);
			
			return $params;
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('解析報表資料發生錯誤');
		}
	}
	
	/* 
	 * @params: array
	 * @return: array
	 */
	private function _parsingProduct($params)
	{
		#取得的清單已可直接使用, 無須再特別處理
		$header = ['產品名稱', 'ERP料號', '簡碼', '售價', '單位', 
						'營運中心', '出貨工廠', '倉別', '上架狀態', '備註'];
		
		#整理格式即可
		$list = collect($params->productList)->sortBy('shortCode')->map(function($item, $key){
			$item['price']	= round($item['price'], 2);
			$item['status'] = boolval($item['status']);
			$item['memo'] 	= (Str::length($item['memo']) == 1 && intval($item['memo']) == 0) ? '' : $item['memo'];
			
			return $item;
		})->all();
		
		$params->set('product.header', $header);
		$params->set('product.list', $list);
	}
	
	/* 
	 * @params: array
	 * @return: array
	 */
	private function _parsingPreorderProduct($params)
	{
		#取得的清單已可直接使用, 無須再特別處理
		$header = ['產品名稱', '簡碼', '單位', '營運中心', '上架狀態', '備註'];
		
		#整理格式即可
		$list = collect($params->preorderProductList)->sortBy('shortCode')->map(function($item, $key){
			unset($item['productId']);			
			return $item;
		})->values()->all();
		
		$params->set('preorder.header', $header);
		$params->set('preorder.list', $list);
	}
	
	/* 
	 * @params: array
	 * @return: array
	 */
	private function _parsingSupplierProduct($params)
	{
		/* [
			"supplierNo" => "MSJS001"
			"supplierName" => "美食家食材"
			
		]
		*/
		
		#取得的清單已可直接使用, 無須再特別處理
		$header = ['產品名稱', 'ERP料號', '簡碼', '供應商代號', '供應商', '售價', '進貨價', '單位', 
						'營運中心', '出貨工廠', '上架狀態', '備註'];
		
		#整理格式即可
		$list = collect($params->supplierProductList)->sortBy('shortCode')->map(function($item, $key){
			$item['price']			= round($item['price'], 2);
			$item['purchasePrice']	= round($item['purchasePrice'], 2);
			$item['status'] 		= boolval($item['status']);
			
			return $item;
		})->values()->all();
		
		$params->set('supplier.header', $header);
		$params->set('supplier.list', $list);
	}
	
	/* Export data
	 * @params: array
	 * @return: array
	 */
	public function export($sourceData)
	{
		try
		{
			#Build export data for sheets
			$export['一般產品'] 	= $this->_buildExportProduct($sourceData['product']);
			
			if ($sourceData['hasPreorder'])
				$export['預購產品'] 	= $this->_buildExportPreorderProduct($sourceData['preorder']);
			
			if ($sourceData['hasSupplier'])
				$export['供應商產品']	= $this->_buildExportSupplierProduct($sourceData['supplier']);
			
			#Write export to file
			$brandName = Brand::tryFrom($sourceData['brandId'])->label();
			$fileName = Str::replaceArray('?', [$brandName, $sourceData['exportName']], '?_?.xlsx');
			$filePath = Storage::disk('export')->path($fileName);
			
			$writer = new Writer();
			$writer->openToFile($filePath);
			
			$centerStyle = (new Style())->setCellAlignment(CellAlignment::CENTER);
			
			$index = 0;
			foreach($export as $sheetName => $sheetData)
			{
				$sheet = ($index == 0) ? $writer->getCurrentSheet() : $writer->addNewSheetAndMakeItCurrent();
				$sheet->setName($sheetName);
				
				foreach($sheetData as $data)
				{
					$row =  Row::fromValues($data, $centerStyle);
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
	private function _buildExportProduct($exportData)
	{
		$export = [];
		$export[] = $exportData['header'];
		
		foreach($exportData['list'] as $data)
		{
			$row = [];
			
			$row[] = $data['productName'];
			$row[] = $data['erpNo'];
			$row[] = $data['shortCode'];
			$row[] = Number::currency(intval(data_get($data, 'price', 0)), precision: 0);
			$row[] = $data['unit'];
			$row[] = $data['opCenter'];
			$row[] = $data['factoryName']; 
			$row[] = "{$data['warehouseNo']} {$data['warehouse']}";
			$row[] = $data['status'] ? '上架' : '下架';
			$row[] = $data['memo'];
			
			$export[] = $row;
		}
		
		return $export;
	}
	
	/* Build data for export
	 * @params: array
	 * @return: array
	 */
	private function _buildExportPreorderProduct($exportData)
	{
		$export = [];
		$export[] = $exportData['header'];
		
		foreach($exportData['list'] as $data)
		{
			$row = [];
			
			$row[] = $data['productName'];
			$row[] = $data['shortCode'];
			$row[] = $data['unit'];
			$row[] = $data['opCenter'];
			$row[] = $data['status'] ? '上架' : '下架';
			$row[] = $data['memo'];
			
			$export[] = $row;
		}
		
		return $export;
	}
	
	/* Build data for export
	 * @params: array
	 * @return: array
	 */
	private function _buildExportSupplierProduct($exportData)
	{
		$export = [];
		$export[] = $exportData['header'];
		
		['產品名稱', 'ERP料號', '簡碼', '供應商代號', '供應商', '售價', '進貨價', '單位', 
						'營運中心', '出貨工廠', '上架狀態', '備註'];
		foreach($exportData['list'] as $data)
		{
			$row = [];
			
			$row[] = $data['productName'];
			$row[] = $data['erpNo'];
			$row[] = $data['shortCode'];
			$row[] = $data['supplierNo'];
			$row[] = $data['supplierName'];
			$row[] = Number::currency(intval(data_get($data, 'price', 0)), precision: 0);
			$row[] = Number::currency(intval(data_get($data, 'purchasePrice', 0)), precision: 0);
			$row[] = $data['unit'];
			$row[] = $data['opCenter'];
			$row[] = $data['factoryName']; 
			$row[] = $data['status'] ? '上架' : '下架';
			$row[] = $data['memo'];
			
			$export[] = $row;
		}
		
		return $export;
	}
}
