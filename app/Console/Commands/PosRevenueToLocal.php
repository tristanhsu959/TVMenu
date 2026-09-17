<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class PosRevenueToLocal extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pos:revenue-local {}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Bafang & Buygood pos revenue to local db';
	
	protected $cacheKey = 'bafang:buygood:pos:revenue';
	
    /**
     * Execute the console command.
     */
    public function handle()
    {
        list($stDate, $endDate) = $this->_getParams();
		
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
	
	private function _getParams()
	{
		if (Cache::has($this->cacheKey))
			$lastUpdate = Cache::get($this->cacheKey); #array
		else
			$lastUpdate = ['BF' => '2024-12-01', 'BG' => '2024-12-01']; #因為要從2025開始
		
		#Cache::put($params->cacheKey, $this->_statistics, now()->addMinutes(10));
				
		/* $bfStMonth	= Carbon::parse($lastUpdate['BF'])->format('Y-m-d 00:00:00');
			$endDate 	= Carbon::parse($endDate)->format('Y-m-d 23:59:59');
		}
		else
		{
			
			
			if ($result) #取最後更新時間
				$stDate = Carbon::parse($result['saleDate'])->subMinutes(30)->format('Y-m-d H:i:s');
			else if ($stDate) #取指定的開始時間
				$stDate = Carbon::parse($stDate)->format('Y-m-d 00:00:00');
			else
				$stDate = now()->subMinutes(30)->format('Y-m-d 00:00:00');
			
			$endDate = Carbon::parse($stDate)->addDay()->subMinutes(30)->format('Y-m-d H:i:s');
		}
		 */
		/* return [$stDate, $endDate]; */
	}
	
	/* 取營收客單統計資料By Month
	 * @params: enums
	 * @params: datetime
	 * @params: datetime
	 * @return: array
	 */
	public function getDataByAverageOrderValue($brand, $userAreaIds, $stDate, $endDate, $shopType)
	{
		$brandId = $brand->value;
		$excepts = config("web.sales.shop.except.{$brandId}");
		
		if ($brand == Brand::BAFANG)
			$db = $this->connectBFPosErp();
		else if ($brand == Brand::BUYGOOD)
			$db = $this->connectBGPosErp();
		else
			return [];
		
		$authAreaIds = AreaLib::toSalesAreaId($brand, $userAreaIds);
		
		$result = $db
				->table(DB::raw('SHOP00 as a WITH(NOLOCK)'))
				->join(DB::raw('SALE00 as b WITH(NOLOCK)'), 'b.SHOP_ID', '=', 'a.SHOP_ID')
				->whereIn('a.gid', $authAreaIds)
				->whereIn('a.SHOP_KIND', $shopType)
				->whereNotIn('a.SHOP_ID', $excepts)
				->where('b.STATUS', '=', 2) #3:作廢不計入
				->where('b.SALE_DATE', '>=', $stDate)
				->where('b.SALE_DATE', '<', $endDate)
				#->select('a.SHOP_ID as shopId', 'c.Sk_name as typeName', 'a.gid as areaId')
				->select('a.SHOP_KIND as shopKind', 'a.gid as areaId')
				->selectRaw('DATEADD(month, DATEDIFF(month, 0, b.SALE_DATE), 0) as saleDate')
				->selectRaw('count(distinct a.SHOP_ID) as shopCount')
				->selectRaw('count(a.SHOP_ID) as visitors')
				->selectRaw('sum(b.amount) as amount')
				->selectRaw('sum(b.TOT_SALES) as totalSales')
				->selectRaw('sum(b.TOT_EXTRA) as totalExtra')
				->selectRaw('sum(b.TOT_DISCHARGE) as totalDischarge')
				->groupBy('a.SHOP_KIND', 'a.gid', DB::raw('DATEADD(month, DATEDIFF(month, 0, b.SALE_DATE), 0)'))#->ddRawSql();
				->get()
				->toArray();
		
		return $result; 
	}
}
