<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class BafangPosOrderReplication extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bafang:pos-order-replication {argStDate?} {argEndDate?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Replication Bafang Pos Sale01 to New Table';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $argStDate 	= $this->argument('argStDate');
		$argEndDate = $this->argument('argEndDate');
		
		list($stDate, $endDate) = $this->_getParams($argStDate, $argEndDate);
		
		try
		{
			$this->info("Fetch bafang data start -----" . now());
			Log::channel('commandLog')->info("Fetch bafang data start" . now(), [ __class__, __function__, __line__]);
			
			#複寫SALE01至z-sd-order
			DB::connection('BFPosErp')->statement("
				INSERT INTO zs_sd_order WITH (TABLOCK)
				(saleId, saleSno, shopId, productId, price, qty, discount, taste, saleDate)
				SELECT 
					a.SALE_ID,
					a.SALE_SNO,
					a.SHOP_ID,
					a.PROD_ID,
					a.SALE_PRICE,
					a.QTY,
					a.ITEM_DISC,
					a.TASTE_MEMO,
					b.SALE_DATE
				FROM SALE01 a WITH(NOLOCK)
				JOIN SALE00 b WITH(NOLOCK)
					ON a.SHOP_ID = b.SHOP_ID
					AND a.SALE_ID = b.SALE_ID
				WHERE
					b.STATUS = '2'
					AND b.SALE_DATE BETWEEN ? AND ?
					AND NOT EXISTS (
						SELECT 1
						FROM zs_sd_order s
						WHERE s.shopId = a.SHOP_ID
						AND s.saleId = a.SALE_ID
						AND s.saleSno = a.SALE_SNO
					)", [$stDate, $endDate]);
					
			$this->info("Fetch bafang data completed -----" . now());
			Log::channel('commandLog')->info("Fetch bafang data completed" . now(), [ __class__, __function__, __line__]);
		}
		catch(Exception $e)
		{
			Log::channel('commandLog')->error('Fetch bafang data : ' . $e->getMessage(), [ __class__, __function__, __line__]);
			$this->fail($e->getMessage());
		}
    }
	
	private function _getParams($stDate, $endDate)
	{
		#有日期參數:手動更新, 只要少一個參數就走每日更新模式
		if (! empty($stDate) && ! empty($endDate))
		{
			$stDate		= Carbon::parse($stDate)->format('Y-m-d 00:00:00');
			$endDate 	= Carbon::parse($endDate)->format('Y-m-d 23:59:59');
		}
		else
		{
			$result = DB::connection('BFPosErp')->selectOne("SELECT TOP 1 saleDate
					FROM zs_sd_order WITH (INDEX(idx_zs_sd_order_saleDate))
					ORDER BY saleDate DESC");
			
			if ($result) #取最後更新時間
				$stDate = Carbon::parse($result['saleDate'])->subMinutes(30)->format('Y-m-d H:i:s');
			else if ($stDate) #取指定的開始時間
				$stDate = Carbon::parse($stDate)->format('Y-m-d 00:00:00');
			else
				$stDate = now()->subMinutes(30)->format('Y-m-d 00:00:00');
			
			$endDate = Carbon::parse($stDate)->addDay()->subMinutes(30)->format('Y-m-d H:i:s');
		}
		
		return [$stDate, $endDate];
	}
}
