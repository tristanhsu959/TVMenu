<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Facades\AppManager;
use App\Models\CurrentUser;

class AuthMiddleware
{
	/**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
		$currentUser = AppManager::getCurrentUser();
		
		if (empty($currentUser))
			return redirect()->route('signin')->with('msg', '認證已過期，請重新登入');
		
        return $next($request);
    }
}
