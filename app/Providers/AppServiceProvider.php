<?php

namespace App\Providers;

use App\Manager\AppManager;
use App\Manager\PosManager;
use App\Manager\PurchaseManager;
use App\Manager\StoreManager;
use App\Manager\LocalLegacyManager;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Events\StatementPrepared;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
use PDO;
use App\ViewModels\MenuViewModel;
use Illuminate\Support\Facades\Blade;
use SocialiteProviders\Manager\SocialiteWasCalled;
use App\Socialite\WebcommProvider;

class AppServiceProvider extends ServiceProvider
{
	/**
     * Register any application services.
     */
    public function register(): void
    {
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
