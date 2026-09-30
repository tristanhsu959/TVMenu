<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Middleware\Api\AccessMiddleware;
use App\Http\Controllers\Api\MediaController;
use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\StoreMenuController;

Route::middleware([AccessMiddleware::class])->group(function(){
	
	/* Media */
	Route::get('tvMenu/medias/{active?}', [MediaController::class, 'list'])->name('media');
	Route::post('tvMenu/medias', [MediaController::class, 'create'])->name('media.create');
	Route::get('tvMenu/medias/{id}', [MediaController::class, 'detail'])->name('media.detail');
	Route::put('tvMenu/medias/{id}', [MediaController::class, 'update'])->name('media.update');
	Route::delete('tvMenu/medias/{id}', [MediaController::class, 'delete'])->name('media.delete');
	
	/* Menu */
	Route::get('tvMenu/menus', [MenuController::class, 'list'])->name('menu');
	Route::post('tvMenu/menus', [MenuController::class, 'create'])->name('menu.create');
	Route::get('tvMenu/menus/{id}', [MenuController::class, 'detail'])->name('menu.detail');
	Route::put('tvMenu/menus/{id}', [MenuController::class, 'update'])->name('menu.update');
	Route::delete('tvMenu/menus/{id}', [MenuController::class, 'delete'])->name('menu.delete');
	
	/* Store Menu Mapping */
	Route::get('tvMenu/storeMenus/{brand?}', [StoreMenuController::class, 'list'])->name('storeMenu');
	Route::get('tvMenu/storeMenus/{id}', [StoreMenuController::class, 'detail'])->name('storeMenu.detail');
	Route::post('tvMenu/storeMenus/{id}', [StoreMenuController::class, 'upsert'])->name('storeMenu.upsert'); #insert or update
	Route::delete('tvMenu/storeMenus/{id}', [StoreMenuController::class, 'delete'])->name('storeMenu.delete');
});


/* sanctum sample 
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum'); */

