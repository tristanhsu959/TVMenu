<?php

namespace App\Console\Commands;

use App\Facades\LegacyManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class LegacyExtraOrderToLocal extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'legacy:extra-order-to-local {argStDate?} {argEndDate?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Copy old sytem extra order to Local';
	
	/**
     * Execute the console command.
     */
    public function handle()
    {
        try
		{
			Log::channel('commandLog')->info("Fetch extra data start : " . now(), [ __class__, __function__, __line__]);
			
			$argStDate 	= $this->argument('argStDate');
			$argEndDate = $this->argument('argEndDate');
		
			list($stDate, $endDate) = $this->_getParams($argStDate, $argEndDate);
			
			Log::channel('commandLog')->info(Str::replaceArray('?', [$stDate, $endDate], "Params:?~? -----"));
			
			$orderData = $this->_fetchOrder($stDate, $endDate);
			
			$this->_updateToLocal($orderData);
			
			Log::channel('commandLog')->info(Str::replaceArray('?', [count($orderData), now()], "Fetch buygood data completed:? ----- ?"), [ __class__, __function__, __line__]);
		}
		catch(Exception $e)
		{
			Log::channel('commandLog')->error('Fetch buygood data : ' . $e->getMessage(), [ __class__, __function__, __line__]);
			$this->fail($e->getMessage());
		}
    }
	
	private function _getParams($argStDate, $argEndDate)
	{
		#2026-02有5萬多筆,應是春節期間從舊系統建的資料
		$this->info("Build params start ----- " . now());
		
		if (empty($argStDate) OR empty($argEndDate))
		{
			$stDate = now()->subHours(5)->format('Y-m-d H:i:s');
			$endDate= now()->format('Y-m-d H:i:s');
		}
		else
		{
			$stDate	= Carbon::parse($argStDate)->format('Y-m-d 00:00:00');
			$endDate= Carbon::parse($argEndDate)->addDay()->format('Y-m-d 00:00:00');
		}
		
		$this->info(Str::replaceArray('?', [$stDate, $endDate], "Build params end -----?~?"));
		
		return [$stDate, $endDate];
	}
	
	/* private function _getLastTime()
	{
		$query = DB::connection('SalesDashboard');
		$result = $query->table('legacy_extra_order')
					->select('expectedDate')
					->orderBy('expectedDate', 'desc')
					->limit(1, 0)
					->first();
					
		return $result['expectedDate'];
	} */
	
	private function _fetchOrder($stDate, $endDate)
	{
		$this->info(Str::replaceArray('?', [now()], "Fetch extra data start -----?"));
		
		/*0 => array:9 [
			"shortCode" => "0201"
			"productName" => "白豆漿"
			"storeNo" => "1100"
			"expectedDate" => "2026-05-01"
			"qty" => "5.00"
			"amount" => "200.00"
			"factoryNo" => "TW_TP"
			"factoryName" => "淡水總廠"
			"isExtra" => true
		]
		*/
		
		$result = LegacyManager::getExtraData($stDate, $endDate);	
		
		$this->info(Str::replaceArray('?', [now()], "Fetch extra data completed -----?"));
			
		return $result;
	}
	
	private function _updateToLocal($orderData)
	{
		if (empty($orderData))
			return TRUE;
		
		$this->info(Str::replaceArray('?', [now()], "Update extra data to local start -----?"));
			
		#(saleId, saleSno, shopId, productId, price, qty, discount, taste, saleDate, updateAt)
		$query = DB::connection('SalesDashboard')->table('legacy_extra_order');
		$upsert = collect($orderData)->chunk(100);
		
		foreach ($upsert as $items) 
		{
			$rows = [];
			
			foreach($items as $item)
			{
				$row = [];
				$row['legacyId']	= $item['legacyId'];
				$row['expectedDate']= $item['expectedDate'];
				$row['storeNo']		= $item['storeNo'];
				$row['shortCode']  	= $item['shortCode'];
				$row['productName'] = $item['productName'];
				$row['qty']     	= $item['qty'];
				$row['amount']      = $item['amount'];
				$row['factoryNo']  	= $item['factoryNo'];
				$row['factoryName'] = $item['factoryName'];
				$row['updateAt'] 	= now()->format('Y-m-d H:i:s');
			
				$rows[] = $row;
			}
				
			$result = $query->upsert(
				$rows,
				['legacyId', 'factoryNo'],
				['qty', 'amount', 'updateAt']
			);
		}
		
		$this->info(Str::replaceArray('?', [now()], "Update extra data to local completed -----?"));
			
		return $result;
	}
}
