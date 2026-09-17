<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Session\TokenMismatchException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
		then: function () {
            Route::middleware('web')
                ->prefix('bafang')
                ->name('bafang.')
                ->group(base_path('routes/bafang.php'));
			
			Route::middleware('web')
                ->prefix('buygood')
                ->name('buygood.')
                ->group(base_path('routes/buygood.php'));
				
			Route::middleware('web')
                ->prefix('fjveggie')
                ->name('fjveggie.')
                ->group(base_path('routes/fjveggie.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
		#預設會濾除空白字元
        $middleware->trimStrings(except: [
			'adPassword',
		]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, $request) {
            if ($e->getStatusCode() === 419) {
				return redirect()->route('signin')
						->with('msg', '您的連線已過期，請重新登入');	
			}
        });
    })->create();
