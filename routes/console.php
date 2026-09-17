<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

use Illuminate\Support\Facades\Schedule;
use App\Console\Commands\BafangPosOrderReplication;
use App\Console\Commands\BuygoodPosOrderReplication;

use App\Console\Commands\BafangPosSale00;
use App\Console\Commands\BafangPosSale01;

use App\Console\Commands\BuygoodPosSale00;
use App\Console\Commands\BuygoodPosSale01;

use App\Console\Commands\BafangPosOrderToLocal;
use App\Console\Commands\BuygoodPosOrderToLocal;

#* * * * * cd /var/www/html/SalesDashboard && php artisan schedule:run >> /dev/null 2>&1

#Bafang pos sale00(門店營收)
Schedule::command('bafang:pos-sale00')->everyTenMinutes()->withoutOverlapping(5);

#Buygood pos sale00(門店營收)
Schedule::command('buygood:pos-sale00')->everyTenMinutes()->withoutOverlapping(5);

#Bafang zs_sd_order(銷售統計)
Schedule::command('bafang:pos-order-replication')->hourly()->withoutOverlapping(10); #->between('10:00', '22:00');

#Buygood zs_sd_order(銷售統計)
Schedule::command('buygood:pos-order-replication')->hourly()->withoutOverlapping(10); #->between('10:00', '22:00');

#舊系統追加 To Local
Schedule::command('legacy:extra-order-to-local')->everyFourHours()->withoutOverlapping(); #->between('10:00', '22:00');

/* #Update data for current day
#橙汁排骨
Schedule::command('new-release:update-to-local porkRibs 1')->everyFifteenMinutes()->between('10:00', '21:00');
#蕃茄牛三寶
Schedule::command('new-release:update-to-local tomatoBeef 1')->everyFifteenMinutes()->between('10:00', '21:00');
#主廚秘製滷肉飯
Schedule::command('new-release:update-to-local braisedPork 1')->everyFifteenMinutes()->between('10:00', '21:00');
#老皮嫩肉
Schedule::command('new-release:update-to-local eggTofu 1')->everyFifteenMinutes()->between('10:00', '21:00');
#秘製滷肉汁
Schedule::command('new-release:update-to-local braisedGravy 1')->everyFifteenMinutes()->between('10:00', '21:00');

#Update data for last 3 day
#橙汁排骨
Schedule::command('new-release:update-to-local porkRibs 3')->dailyAt('23:00'); 
#蕃茄牛三寶
Schedule::command('new-release:update-to-local tomatoBeef 3')->dailyAt('23:05');
#主廚秘製滷肉飯
Schedule::command('new-release:update-to-local braisedPork 3')->dailyAt('23:10');
#老皮嫩肉
Schedule::command('new-release:update-to-local eggTofu 3')->dailyAt('23:15');
#秘製滷肉汁
Schedule::command('new-release:update-to-local braisedGravy 3')->dailyAt('23:20'); */

/*
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
*/

#Bafang zs_sd_order
/* Schedule::command('bafang:pos-order-statistics')
		->everyFifteenMinutes()
		->when(fn () => now()->between('10:00', '21:00'))
		->withoutOverlapping(10); 

Schedule::command('bafang:pos-sale01')
		#->hourly()
		->everyThirtyMinutes()
		#->unlessBetween('10:00', '21:00')
		->withoutOverlapping(10);
		
#Buygood zs_sd_order
Schedule::command('buygood:pos-order-statistics')
		->everyFifteenMinutes()
		->when(fn () => now()->between('10:00', '21:00'))
		->withoutOverlapping(10);

Schedule::command('buygood:pos-sale01')
		#->hourly()
		->everyThirtyMinutes()
		#->unlessBetween('10:00', '21:00')
		->withoutOverlapping(10);
*/	