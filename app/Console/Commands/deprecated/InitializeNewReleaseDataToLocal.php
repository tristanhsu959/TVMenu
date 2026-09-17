<?php

namespace App\Console\Commands;

use App\Services\Commands\PosInitializeService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InitializeNewReleaseDataToLocal extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'new-release:initialize-to-local {configKey?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Initialize Pos Data to Local DB';

    /**
     * Execute the console command.
     */
    public function handle(PosInitializeService $posService)
    {
        try
		{
			$configKeys = [];
			
			#只執行指定table的參數
			$argument = $this->argument('configKey');
			
			if (empty($argument))
			{
				$list = config('buygood.new_release.products');
				$configKeys = array_keys($list);
			}
			else
				$configKeys[] = $argument;
			
			foreach($configKeys as $configKey)
			{
				$productName = config("buygood.new_release.products.{$configKey}.name");
				Log::channel('commandLog')->info("Initialize Start : {$productName}", [ __class__, __function__, __line__]);
			
				$this->info("Initialize Start : {$productName} -----");
				#新品目前似乎只有梁社漢有
				$posService->setConfig($configKey);
				
				#1. Get params fetch date
				$this->info('Get Params-----');
				$params = $posService->getParams();
				
				#for testing
				// data_set($params, 'stDate', '2025-11-01');
				// data_set($params, 'endDate', '2025-12-22');
				
				$this->info(json_encode($params));
							
				#2. Get POS DB data
				$this->info('Fetch data from POSDB -----');
				$posData = [];
				$posData = $posService->getDataFromPosDB($params);
				$count = count($posData);
				$this->info("Data count : {$count} -----");
				// Log::channel('commandLog')->info("Data : ". json_encode($posData));
				// return;
				#3. Save data to local
				$this->info('Save Data to Local -----');
				$posService->saveToLocalDB($posData);
				
				$this->info("Initialize {$productName} completed -----");
				Log::channel('commandLog')->info("Initialize {$productName} completed", [ __class__, __function__, __line__]);
			}
		}
		catch(Exception $e)
		{
			Log::channel('commandLog')->error('Initialize : ' . $e->getMessage(), [ __class__, __function__, __line__]);
			$this->fail($e->getMessage());
		}
    }
}
