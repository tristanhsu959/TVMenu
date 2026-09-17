<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Facades\AppManager;
use App\Models\CurrentUser;

class AccessPermissionMiddleware
{
	/**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $code): Response
    {
		$currentUser = AppManager::getCurrentUser();
		
		if (empty($currentUser->rolePermission) && ! $currentUser->isSupervisor())
			return redirect()->route('signin')->with('msg', '使用者尚無系統授權');
		
		if (! $currentUser->hasPermissionTo($code) && ! $currentUser->isSupervisor())
			abort(403);
		
        return $next($request);
    }
}
