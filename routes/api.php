<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Middleware\Api\AccessMiddleware;
use App\Http\Controllers\Api\MediaController;
use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\StoreMenuController;

Route::middleware([AccessMiddleware::class])->group(function(){
	
	/* Media */
	Route::get('tvMenu/medias', [MediaController::class, 'list'])->name('media');
	Route::get('tvMenu/medias/active', [MediaController::class, 'activeList'])->name('media');
	Route::post('tvMenu/medias', [MediaController::class, 'create'])->name('media.create');
	Route::get('tvMenu/medias/{id}', [MediaController::class, 'detail'])->name('media.detail')->whereNumber('id');
	Route::put('tvMenu/medias/{id}', [MediaController::class, 'update'])->name('media.update')->whereNumber('id');
	Route::delete('tvMenu/medias/{id}', [MediaController::class, 'delete'])->name('media.delete')->whereNumber('id');
	
	/* Menu */
	Route::get('tvMenu/menus', [MenuController::class, 'list'])->name('menu');
	Route::post('tvMenu/menus', [MenuController::class, 'create'])->name('menu.create');
	Route::get('tvMenu/menus/{id}', [MenuController::class, 'detail'])->name('menu.detail')->whereNumber('id');
	Route::put('tvMenu/menus/{id}', [MenuController::class, 'update'])->name('menu.update')->whereNumber('id');
	Route::delete('tvMenu/menus/{id}', [MenuController::class, 'delete'])->name('menu.delete')->whereNumber('id');
	
	/* Store Menu Mapping */
	Route::get('tvMenu/storeMenus/{brand?}', [StoreMenuController::class, 'list'])->name('storeMenu')->where('brand', '8WAY|8way|BUYGOOD|buygood');
	Route::post('tvMenu/storeMenus', [StoreMenuController::class, 'upsert'])->name('storeMenu.upsert'); #insert or update
	Route::get('tvMenu/storeMenus/{id}', [StoreMenuController::class, 'detail'])->name('storeMenu.detail');
	Route::delete('tvMenu/storeMenus/{id}', [StoreMenuController::class, 'delete'])->name('storeMenu.delete');
});


/* sanctum sample 
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum'); */

