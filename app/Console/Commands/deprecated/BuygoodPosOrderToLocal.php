<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class BuygoodPosOrderToLocal extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'buygood:pos-order-to-local';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Copy Buygood Pos Sale01 to Local';
	
	protected $cacheKey = 'buygood:fetchTime';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try
		{
			Log::channel('commandLog')->info("Fetch buygood data start : " . now(), [ __class__, __function__, __line__]);
			
			list($stDate, $endDate) = $this->_getParams();
			
			Log::channel('commandLog')->info(Str::replaceArray('?', [$stDate, $endDate], "Params:?~? -----"));
			
			$orderData = $this->_fetchOrder($stDate, $endDate);
			
			$this->_updateToLocal($orderData);
			
			Cache::put($this->cacheKey, $endDate, now()->addMinutes(60));
			
			Log::channel('commandLog')->info(Str::replaceArray('?', [count($orderData), now()], "Fetch buygood data completed:? ----- ?"), [ __class__, __function__, __line__]);
		}
		catch(Exception $e)
		{
			Log::channel('commandLog')->error('Fetch buygood data : ' . $e->getMessage(), [ __class__, __function__, __line__]);
			$this->fail($e->getMessage());
		}
    }
	
	private function _getParams()
	{
		$this->info("Build params start ----- " . now());
		
		if (Cache::has($this->cacheKey))
			$stDate = Cache::get($this->cacheKey);
		else
			$stDate = '2025-09-01';
		
		$stDate = Carbon::parse($stDate)->subMinutes(5)->format('Y-m-d H:i:s');
		$endDate = Carbon::parse($stDate)->addMinutes(60);
		
		if (Carbon::parse($endDate)->isAfter(now())) 
			$endDate = now()->format('Y-m-d H:i:s');
		else
			$endDate = $endDate->format('Y-m-d H:i:s');
		
		$this->info(Str::replaceArray('?', [$stDate, $endDate], "Build params end -----?~?"));
		
		return [$stDate, $endDate];
	}
	
	private function _fetchOrder($stDate, $endDate)
	{
		$this->info(Str::replaceArray('?', [now()], "Fetch buygood data start -----?"));
			
		#無stDate時, carbon default is now
		#(saleId, saleSno, shopId, productId, price, qty, discount, taste, saleDate)
		$result = DB::connection('BGPosErp')
			->table('SALE00 as s0')
			->fromRaw('SALE00 as s0 WITH(NOLOCK)')
			->join(DB::RAW('SALE01 as s1 WITH(NOLOCK)'), function($join){
				$join->on('s1.SHOP_ID', '=', 's0.SHOP_ID')
					->on('s1.SALE_ID', '=', 's0.SALE_ID');
			})
			->where('s0.STATUS', '=', '2') 
			->where('s0.SALE_DATE', '>=', $stDate)
			->where('s0.SALE_DATE', '<=', $endDate)
			->select('s1.SALE_ID', 's1.SALE_SNO', 's1.SHOP_ID', 's1.PROD_ID')
			->addSelect('s1.SALE_PRICE', 's1.QTY', 's1.ITEM_DISC', 's1.TASTE_MEMO', 's0.SALE_DATE')
			->get()
			->toArray();
			#->toRawSql(); 
			
		$this->info(Str::replaceArray('?', [now()], "Fetch buygood data completed -----?"));
			
		return $result;
	}
	
	private function _updateToLocal($orderData)
	{
		if (empty($orderData))
			return TRUE;
		
		$this->info(Str::replaceArray('?', [now()], "Update buygood data to local start -----?"));
			
		#(saleId, saleSno, shopId, productId, price, qty, discount, taste, saleDate, updateAt)
		$query = DB::connection('PosOrder')->table('bg_sale01');
		$upsert = collect($orderData)->chunk(100);
		
		foreach ($upsert as $items) 
		{
			$rows = [];
			
			foreach($items as $item)
			{
				$row = [];
				$row['saleId']  	= $item['SALE_ID'];
				$row['saleSno'] 	= $item['SALE_SNO'];
				$row['shopId']  	= $item['SHOP_ID'];
				$row['productId'] 	= $item['PROD_ID'];
				$row['price']     	= $item['SALE_PRICE'];
				$row['qty']       	= $item['QTY'];
				$row['discount']  	= $item['ITEM_DISC'];
				$row['taste']     	= $item['TASTE_MEMO'];
				$row['saleDate']  	= $item['SALE_DATE'];
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
		
		$this->info(Str::replaceArray('?', [now()], "Update buygood data to local completed -----?"));
			
		return $result;
	}
}
