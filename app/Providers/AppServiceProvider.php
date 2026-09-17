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
        // 綁定單例
		$this->app->singleton(AppManager::class, function ($app) {
			return new \App\Manager\AppManager();
		});
		
		$this->app->singleton(StoreManager::class, function ($app) {
			return $app->build(StoreManager::class);
		});
		
		$this->app->singleton(PosManager::class, function ($app) {
			return $app->build(PosManager::class);
		});
		
		$this->app->singleton(PurchaseManager::class, function ($app) {
			return $app->build(PurchaseManager::class);
		});
		
		$this->app->singleton(LocalLegacyManager::class, function ($app) {
			return $app->build(LocalLegacyManager::class);
		});
		
		/* $this->app->singleton(LegacyManager::class, function ($app) {
			return $app->build(LegacyManager::class);
		}); */

    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
		
        #20251128 Tristan: DB Collection to Assoc Array
		Event::listen(StatementPrepared::class, function ($event) {
			$event->statement->setFetchMode(PDO::FETCH_ASSOC);
		});
		
		#Webcomm oidc
		Event::listen(SocialiteWasCalled::class, function (SocialiteWasCalled $event) {
            $event->extendSocialite('webcomm', WebcommProvider::class);
        });
		
		#View share not work, because session is not available
		#Deprecated
		/*View::composer('*', function ($view) {
			
			if (in_array($view->getName(), ['signin']) == FALSE)
			{				
				$signinInfo = $this->getSigninUserInfo();
				$appMenu = new MenuViewModel($this->getAuthorizedMenu());
				
				if ($signinInfo) 
					$view->with('signinInfo', $signinInfo)->with('appMenu', $appMenu);
			}
		});*/
    }
}
