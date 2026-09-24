<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Middleware\Api\AccessMiddleware;
use App\Http\Controllers\Api\MediaController;
use App\Http\Controllers\Api\MenuController;

Route::middleware([AccessMiddleware::class])->group(function(){
	
	/* Media */
	Route::get('tvMenu/medias', [MediaController::class, 'list'])->name('media');
	Route::get('tvMenu/medias/active', [MediaController::class, 'activeList'])->name('media.active');
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
	Route::get('tvMenu/stores', [StoreController::class, 'list'])->name('store');
	Route::get('tvMenu/stores/{id}', [StoreController::class, 'detail'])->name('store.detail');
	Route::post('tvMenu/stores/{id}', [StoreController::class, 'upsert'])->name('store.upsert'); #insert or update
	Route::delete('tvMenu/stores/{id}', [StoreController::class, 'delete'])->name('store.delete');
});


/* sanctum sample 
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum'); */

