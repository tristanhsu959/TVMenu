<?php

namespace App\Console\Commands;

use App\Libraries\Sales\AreaLib;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class BafangPosOrderToLocal extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bafang:pos-order-to-local {argStDate?} {argEndDate?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Copy Bafang Pos Sale01 to Local';

	protected $cacheKey = 'bafang:fetchTime';
    /**
     * Execute the console command.
     */
    public function handle()
    {
        try
		{
			Log::channel('commandLog')->info("Fetch bafang data start : " . now(), [ __class__, __function__, __line__]);
			
			$argStDate 	= $this->argument('argStDate');
			$argEndDate = $this->argument('argEndDate');
			
			list($stDate, $endDate) = $this->_getParams($argStDate, $argEndDate);
			
			Log::channel('commandLog')->info(Str::replaceArray('?', [$stDate, $endDate], "Params:?~? -----"));
			
			$orderData = $this->_fetchOrder($stDate, $endDate);
			
			$this->_updateToLocal($orderData);
			
			Cache::put($this->cacheKey, $endDate, now()->addMinutes(60));
			
			Log::channel('commandLog')->info(Str::replaceArray('?', [count($orderData), now()], "Fetch bafang data completed:? ----- ?"), [ __class__, __function__, __line__]);
		}
		catch(Exception $e)
		{
			Log::channel('commandLog')->error('Fetch bafang data : ' . $e->getMessage(), [ __class__, __function__, __line__]);
			$this->fail($e->getMessage());
		}
    }
	
	private function _getParams($argStDate, $argEndDate)
	{
		$this->info("Build params start ----- " . now());
		
		if (empty($argStDate))
			$stDate = Carbon::yesterday()->format('Y-m-d H:i:s');
		else
			$stDate = Carbon::parse($argStDate)->format('Y-m-d H:i:s');
		
		if (empty($argEndDate))
			$endDate = Carbon::parse($stDate)->addDay()->format('Y-m-d H:i:s');
		else
			$endDate = Carbon::parse($argEndDate)->addDay()->format('Y-m-d H:i:s');
		
		$this->info(Str::replaceArray('?', [$stDate, $endDate], "Build params end:[?]~[?] -----"));
		
		return [$stDate, $endDate];
	}
	
	private function _fetchOrder($stDate, $endDate)
	{
		$result = DB::connection('BFPosErp')
			->table('SALE01 as s01')
			->fromRaw('poserp.dbo.SALE01 as s01 WITH(NOLOCK)')
			->join(DB::RAW('poserp.dbo.SALE00 as s00 WITH(NOLOCK)'), function($join){
					$join->on('s00.SALE_ID', '=', 's01.SALE_ID')
							->on('s00.SHOP_ID', '=', 's01.SHOP_ID');
			})
			->join(DB::RAW('poserp.dbo.SHOP00 as s WITH(NOLOCK)'), 's.SHOP_ID', '=', 's01.SHOP_ID')
			->where('s00.SALE_DATE', '>=', $stDate)
			->where('s00.SALE_DATE', '<', $endDate)
			->select('s01.SALE_ID as saleId', 's01.SALE_SNO as saleSno', 's01.SHOP_ID as shopId', 's01.PROD_ID as productId')
			->addSelect('s01.SALE_PRICE as price', 's01.QTY as qty', 's01.ITEM_DISC as discount', 's01.TASTE_MEMO as taste', 's00.SALE_DATE')
			->addSelect('s.SHOP_NAME as shopName', 's.SHOP_KIND as shopKind', 's.gid as areaId')
			->get()
			->toArray();
		
		/* $result = DB::connection('BFPosErp')
			->table('zs_sd_order as s01')
			->fromRaw('poserp.dbo.zs_sd_order as s01 WITH(NOLOCK)')
			->join(DB::RAW('poserp.dbo.SHOP00 as s WITH(NOLOCK)'), 's.SHOP_ID', '=', 's01.shopId')
			->where('s01.saleDate', '>=', $stDate)
			->where('s01.saleDate', '<', $endDate)
			->select('s01.saleId', 's01.saleSno', 's01.shopId', 's01.productId')
			->addSelect('s01.price', 's01.qty', 's01.discount', 's01.taste', 's01.saleDate')
			->addSelect('s.SHOP_NAME as shopName', 's.SHOP_KIND as shopKind', 's.gid as areaId')
			->get()
			->toArray(); */
			
		return $result;
	}
	
	private function _updateToLocal($orderData)
	{
		if (empty($orderData))
			return TRUE;
		
		$this->info(Str::replaceArray('?', [now()], "Update bafang data to local start -----?"));
			
		#(saleId, saleSno, shopId, productId, price, qty, discount, taste, saleDate, updateAt)
		$query = DB::connection('SalesDashboard')->table('bf_sale01');
		$upsert = collect($orderData)->chunk(100);
		
		foreach ($upsert as $items) 
		{
			$rows = [];
			
			foreach($items as $item)
			{
				$row = [];
				$row['saleId']  	= $item['saleId'];
				$row['saleSno'] 	= $item['saleSno'];
				$row['shopId']  	= $item['shopId'];
				$row['shopName']  	= $item['shopName'];
				$row['shopKind']  	= $item['shopKind'];
				$row['areaId']  	= AreaLib::toId($item['areaId']);
				$row['productId'] 	= $item['productId'];
				$row['price']     	= $item['price'];
				$row['qty']       	= $item['qty'];
				$row['discount']  	= $item['discount'];
				$row['taste']     	= Str::contains($item['taste'], '秘製滷肉汁') ? 1 : 0;
				$row['saleDate']  	= Carbon::parse($item['saleDate'])->format('Y-m-d');
				$row['saleDateTime']= $item['saleDate'];
				$row['updateAt'] 	= now()->format('Y-m-d H:i:s');
			
				$rows[] = $row;
			}
				
			$result = $query->upsert(
				$rows,
				[
					'saleId',
					'saleSno',
					'shopId',
				],
				[
					'updateAt'
				]
			);
		}
		
		$this->info(Str::replaceArray('?', [now()], "Update bafang data to local completed -----?"));
			
		return $result;
	}
}
