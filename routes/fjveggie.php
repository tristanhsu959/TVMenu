<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\DailyRevenueController;

use App\Http\Middleware\AuthMiddleware;
use App\Http\Middleware\AccessPermissionMiddleware;
use App\Enums\Functions;

/***** 芳珍 *****/
Route::middleware([AuthMiddleware::class])->group(function(){
	
	/* 本日營收 */
	Route::middleware([AccessPermissionMiddleware::class . Str::start(Functions::FJ_DAILY_REVENUE->value, ':')])->group(function(){
		Route::get('daily_revenue', [DailyRevenueController::class, 'showSearch'])->name('daily_revenue');
		Route::post('daily_revenue/search', [DailyRevenueController::class, 'search'])->name('daily_revenue.search');
		Route::get('daily_revenue/export/{token}', [DailyRevenueController::class, 'export'])->name('daily_revenue.export');
	});
});


