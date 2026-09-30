<?php

namespace App\Providers;

use App\Manager\StoreManager;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Events\StatementPrepared;
use Illuminate\Support\Facades\Event;
use PDO;

class AppServiceProvider extends ServiceProvider
{
	/**
     * Register any application services.
     */
    public function register(): void
    {
		#綁定單例
		
		#可注入
		$this->app->singleton(StoreManager::class, function ($app) {
			return $app->build(StoreManager::class);
		});
		
		#不可注入
		/* $this->app->singleton(StoreManager::class, function ($app) {
			return new \App\Manager\StoreManager();
		}); */
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
		
        #Tristan: DB Collection to Assoc Array
		Event::listen(StatementPrepared::class, function ($event) {
			$event->statement->setFetchMode(PDO::FETCH_ASSOC);
		});
    }
}
