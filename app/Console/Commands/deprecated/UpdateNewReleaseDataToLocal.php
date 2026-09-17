<?php

namespace App\Console\Commands;

use App\Services\Commands\PosUpdateService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class UpdateNewReleaseDataToLocal extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'new-release:update-to-local {configKey} {diffDays?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update Pos Data to Local DB';

    /**
     * Execute the console command.
     */
    public function handle(PosUpdateService $posService)
    {
		try
		{
			#只執行指定table的參數
			$configKey 	= $this->argument('configKey');
			$productName = config("buygood.new_release.products.{$configKey}.name");
			
			$this->info("Update Start : {$productName} -----");
			Log::channel('commandLog')->info("Update Start : {$productName}", [ __class__, __function__, __line__]);
			
			#新品目前似乎只有梁社漢有
			$posService->setConfig($configKey);
			
			#1. Get params fetch date
			$this->info('Get Params-----');
			$params = $posService->getParams($this->argument('diffDays'));
			$this->info(json_encode($params));
			Log::channel('commandLog')->info("Get Params : " .  json_encode($params), [ __class__, __function__, __line__]);
			
			#2. Get POS DB data
			$this->info('Fetch data from POSDB -----');
			$posData = [];
			$posData = $posService->getDataFromPosDB($params);
			$count = count($posData);
			$this->info("Data count : {$count} -----");
			Log::channel('commandLog')->info("Data count : {$count}", [ __class__, __function__, __line__]);
			
			#3. Save data to local
			$this->info('Save Data to Local -----');
			Log::channel('commandLog')->info('Save Data to Local', [ __class__, __function__, __line__]);
			$posService->saveToLocalDB($posData, $params['stDate'], $params['endDate']);
			
			$this->info("Update {$productName} completed -----");
			Log::channel('commandLog')->info("Update {$productName} completed", [ __class__, __function__, __line__]);
		}
		catch(Exception $e)
		{
			Log::channel('commandLog')->error('Update : ' . $e->getMessage(), [ __class__, __function__, __line__]);
			$this->fail($e->getMessage());
		}
    }
}
