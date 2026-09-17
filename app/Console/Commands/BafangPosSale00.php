<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Exception;

class BafangPosSale00 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bafang:pos-sale00 {argStDate?} {argEndDate?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Bafang Pos Sale00';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try
		{
			$argStDate 	= $this->argument('argStDate');
			$argEndDate = $this->argument('argEndDate');
			
			list($stDate, $endDate, $purge) = $this->_getParams($argStDate, $argEndDate);
			
			if ($purge)
				$this->_purgeData();
			
			$this->_fetchData($stDate, $endDate);
		}
		catch(Exception $e)
		{
			Log::channel('commandLog')->error('Fetch bafang sale00 : ' . $e->getMessage(), [ __class__, __function__, __line__]);
			$this->fail($e->getMessage());
		}
    }
	
	private function _getParams($stDate, $endDate)
	{
		$purge = FALSE;
		
		#日期參數:有->手動更新 | 無->每日更新模式
		if (empty($stDate) && empty($endDate))
		{
			$today = Carbon::parse(now())->format('Y-m-d');
			
			$result = DB::connection('BFPosErp')
						->table(DB::RAW('z_sd_sale00 WITH(NOLOCK)'))
						->where('saleDate', '=', $today)
						->orderBy('saleDateTime', 'DESC')
						->select('saleDateTime')
						->first();
			
			$lastDate = data_get($result, 'saleDateTime', NULL);
			
			if (empty($lastDate))
				$stDate = Carbon::parse($today)->format('Y-m-d H:i:s');
			else
				$stDate = Carbon::parse($lastDate)->subMinutes(10)->format('Y-m-d H:i:s'); #防止漏資料或前一天最後一段未更新
			
			$endDate = Carbon::parse(now())->format('Y-m-d H:i:s');			
			
			$purge = Carbon::parse($lastDate)->isBefore(Carbon::today()); 
		}
		else
		{
			$endDate	= empty($endDate) ? $stDate : $endDate; #可只輸一個日期
			$stDate		= Carbon::parse($stDate)->format('Y-m-d H:i:s');
			$endDate	= Carbon::parse($endDate)->addDay()->format('Y-m-d H:i:s');
		}
		
		return [$stDate, $endDate, $purge];
	}
	
	private function _fetchData($stDate, $endDate)
	{
		$this->info("Fetch bafang sale00 start -----" . now());
		$this->info("Fetch {$stDate} to {$endDate}");
		Log::channel('commandLog')->info("Fetch bafang sale00 start" . now(), [ __class__, __function__, __line__]);
		
		#複寫SALE01至z-sd-order
		DB::connection('BFPosErp')->statement("
			INSERT INTO z_sd_sale00 WITH (TABLOCK)
			(shopId, saleId, totalSales, totalExtra, totalDischarge, amount, saleDate, saleDateTime)
			SELECT 
				a.SHOP_ID,
				a.SALE_ID,
				a.TOT_SALES,
				a.TOT_EXTRA,
				a.TOT_DISCHARGE,
				a.amount,
				a.SALE_DATE,
				a.SALE_DATE
			FROM SALE00 as a WITH(NOLOCK)
			WHERE
				a.STATUS = '2'
				AND a.SALE_DATE >= ? AND a.SALE_DATE < ?
				AND NOT EXISTS (
					SELECT 1
					FROM z_sd_sale00 s
					WHERE s.shopId = a.SHOP_ID
					AND s.saleId = a.SALE_ID
			)", [$stDate, $endDate]);
				
		$this->info("Fetch bafang data completed -----" . now());
		Log::channel('commandLog')->info("Fetch bafang sale00 completed" . now(), [ __class__, __function__, __line__]);
	}
	
	private function _purgeData()
	{
		$today = Carbon::parse(now())->format('Y-m-d');
		
		#複寫SALE01至z-sd-order
		DB::connection('BFPosErp')
			->table('z_sd_sale00')
			->fromRaw('z_sd_sale00 WITH(NOLOCK)')
			->where('saleDate', '<', $today)
			->delete();
				
		return TRUE;
	}
}
