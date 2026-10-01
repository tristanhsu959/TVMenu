<?php

use App\Libraries\ResponseLib;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
		then: function () {
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
    })
    ->withExceptions(function (Exceptions $exceptions) {
		$exceptions->render(function (MethodNotAllowedHttpException $e, Request $request) {
            
            if ($request->is('api/*')) 
			{
				$response = ResponseLib::initialize()->fail('Method Not Allowed')->get(); 
                return response()->json($response, 405);
            }
        });
    })->create();
