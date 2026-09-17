<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class BafangPosSale01 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bafang:pos-sale01 {argStDate?} {argEndDate?}';

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
			$this->info("Fetch {$stDate} to {$endDate}");
			Log::channel('commandLog')->info("Fetch bafang data start" . now(), [ __class__, __function__, __line__]);
			
			#複寫SALE01至z-sd-order
			DB::connection('BFPosErp')->statement("
				INSERT INTO z_sd_sale01 WITH (TABLOCK)
				(saleId, saleSno, shopId, shopName, shopKind, gid, productId, price, qty, discount, taste, saleDateTime, saleDate)
				SELECT 
					a.SALE_ID,
					a.SALE_SNO,
					a.SHOP_ID,
					c.SHOP_NAME,
					c.SHOP_KIND,
					c.gid,
					a.PROD_ID,
					a.SALE_PRICE,
					a.QTY,
					a.ITEM_DISC,
					a.TASTE_MEMO,
					b.SALE_DATE,
					b.SALE_DATE
				FROM SALE01 a WITH(NOLOCK)
				JOIN SALE00 b WITH(NOLOCK)
					ON a.SHOP_ID = b.SHOP_ID
					AND a.SALE_ID = b.SALE_ID
				JOIN SHOP00 c WITH(NOLOCK)
					ON c.SHOP_ID = a.SHOP_ID
				WHERE
					b.STATUS = '2'
					AND b.SALE_DATE >= ? AND b.SALE_DATE < ?
					AND NOT EXISTS (
						SELECT 1
						FROM z_sd_sale01 s
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
			$stDate		= Carbon::parse($stDate)->format('Y-m-d H:i:s');
			$endDate 	= Carbon::parse($endDate)->addDay()->format('Y-m-d H:i:s');
		}
		else
		{
			$result = DB::connection('BFPosErp')->selectOne("SELECT TOP 1 saleDate
					FROM z_sd_sale01 WITH (INDEX(IX_z_sd_statistics))
					ORDER BY saleDate DESC");
			
			$lastDate = Carbon::parse($result['saleDate']);
			
			if ($lastDate->isToday())
			{
				#更新區段時間
				$stDate = Carbon::parse(now())->subMinutes(30)->format('Y-m-d H:i:s');
				$endDate = Carbon::parse(now())->subMinutes(5)->format('Y-m-d H:i:s');
			}
			else
			{
				#更新一日資料量
				$stDate = $lastDate->addDay()->format('Y-m-d H:i:s');
				$endDate = Carbon::parse($stDate)->addDay()->format('Y-m-d H:i:s');
			}
		}
		
		return [$stDate, $endDate];
	}
}
