<?php

namespace App\Console\Commands;

use App\Services\Commands\NewOrderService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

#Order data from new order system
class UpdateNewOrderToLocal extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'new-order:update-to-local {limitDays=1} {startDate?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update New Order to Local DB';

    /**
     * Execute the console command.
     */
    public function handle(NewOrderService $_service)
    {
		try
		{
			$this->info("Update Start : New Order -----");
			Log::channel('commandLog')->info("Update Start : New Order", [ __class__, __function__, __line__]);
			
			#1. Get params fetch date
			$this->info('Get Params-----');
			
			$params = $_service->getParams($this->argument('limitDays'), $this->argument('startDate')); 
			
			$this->info(json_encode($params));
			Log::channel('commandLog')->info("Get Params : " .  json_encode($params), [ __class__, __function__, __line__]);
			
			#2. Get POS DB data
			$this->info('Fetch data from New Order DB -----');
			
			$data = [];
			$data = $_service->getDataFromNewOrderDB($params);
			$count = count($data);
			
			$this->info("Data count : {$count} -----");
			Log::channel('commandLog')->info("Data count : {$count}", [ __class__, __function__, __line__]);
			
			#3. Save data to local
			$this->info('Save Data to Local -----');
			Log::channel('commandLog')->info('Save Data to Local', [ __class__, __function__, __line__]);
			
			$_service->saveToLocalDB($data, $params);
			
			$this->info("Update New Order Data completed -----");
			Log::channel('commandLog')->info("Update New Order Data completed", [ __class__, __function__, __line__]);
		}
		catch(Exception $e)
		{
			Log::channel('commandLog')->error('Update New Order : ' . $e->getMessage(), [ __class__, __function__, __line__]);
			$this->fail($e->getMessage());
		}
    }
}
