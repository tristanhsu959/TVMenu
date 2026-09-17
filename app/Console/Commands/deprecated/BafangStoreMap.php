<?php

namespace App\Console\Commands;

use App\Enums\OpCenter;
use App\Enums\Brand;
use App\Enums\Factory;
use App\Libraries\Purchase\AreaLib;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

#先暫不用(未完成)
class BafangStoreMap extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bafang:store-map';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Bafang store mapping to local';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try
		{
			$this->info("Fetch bafang store start -----" . now());
			Log::channel('commandLog')->info("Fetch bafang store start" . now(), [ __class__, __function__, __line__]);
			
			$purchaseStores = $this->_getStoreFromNOrder();
			$posStores = $this->_getStoreFromPos();
			
			$this->info("Fetch bafang store completed -----" . now());
			Log::channel('commandLog')->info("Fetch bafang store completed" . now(), [ __class__, __function__, __line__]);
		}
		catch(Exception $e)
		{
			Log::channel('commandLog')->error('Fetch bafang store : ' . $e->getMessage(), [ __class__, __function__, __line__]);
			$this->fail($e->getMessage());
		}
    }
	
	/**
     * Purchase store list.
     */
	private function _getStoreFromNOrder()
	{
		$brandId = Brand::BAFANG->value;
		
		$db = DB::connection('NewOrder');
		$result = $db
			->table('Store as s')
			->fromRaw('Store as s WITH(NOLOCK)')
			->join(DB::raw('Area as ar WITH(NOLOCK)'), 'ar.Id', '=', 's.AreaId')
			->join(DB::raw('StoreCar as sc WITH(NOLOCK)'), 'sc.StoreId', '=', 's.Id')
			->leftJoin(DB::raw('[User] as u WITH(NOLOCK)'), 'u.Id', '=', 's.SuperviseUserId')
			->leftJoin(DB::raw('Factory as f WITH(NOLOCK)'), 'f.Id', '=', 'sc.FactoryId')
			->select('s.Id as storeId', 's.No as storeNo', 's.Name as storeName', 's.PosId as storePosId', 's.VATNumber as storeVatNumber')
			->addSelect('s.BossName as bossName', 's.StorePhone as storePhone', 's.Address as address', 's.ErpNo as storeErpNo')
			->addSelect('u.Name as salesSupervisor', 's.OpenDate as openDate', 's.CloseDate as closeDate')
			->whereExists(function ($query) use($brandId) {
				$query->select(DB::raw(1))
					->from('OperationCenter as oc')
					->whereColumn('oc.Id', 's.OperationCenterId')
					->whereIn('oc.No', $this->getOpCenterNo($brandId));
			})
			->whereExists(function ($query) use($brandId) {
				$query->select(DB::raw(1))
					->from('Brand as bd')
					->whereColumn('bd.Id', 's.BrandId')
					->where('bd.No',  $this->getBrandNo($brandId));
			})
			->whereExists(function ($query) use($brandId) {
				$query->select(DB::raw(1))
					->from('Factory as ft')
					->whereColumn('ft.Id', 'sc.FactoryId')
					->whereIn('ft.No',  $this->getFactoryNo($brandId));
			})
			->whereNull('s.CloseDate')
			->whereNotIn('s.No', config("web.purchase.store.except.{$brandId}"))#->ddRawSql();
			->get()
			->toArray(); 
		
		return $result;
	}
	
	/**
     * Pos store list.
     */
	public function _getStoreFromPos()
	{
		$configCode = Brand::BAFANG->code();
		$excepts = config("web.sales.shop.except.{$configCode}");
		
		$db = DB::connection('BFPosErp');
		
		$result = $db->table('SHOP00 as a')
			->join('shop_kind as b', 'b.sk_id', '=', 'a.shop_kind')
			->select('a.SHOP_ID as shopPosId', 'a.shop_nickname as shopName', 'a.erp_shopid as shopErpNo', 'a.CODE as shopVatNumber')
			->where('a.closedown', '=', 0)
			->whereNotIn('a.SHOP_ID', $excepts)->ddRawSql();
			#->get();
		
		return $result;
	}
	
	
}
