<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;

Route::middleware([AccessMiddleware::class])->group(function(){
	/***** 新品設定 *****/
	Route::get('new_release/setting', [NewReleaseSettingController::class, 'list'])->name('new_release_setting');
	Route::get('new_release/setting/list', [NewReleaseSettingController::class, 'list'])->name('new_release_setting.list');
	Route::get('new_release/setting/create', [NewReleaseSettingController::class, 'showCreate'])->name('new_release_setting.create');
	Route::post('new_release/setting/create', [NewReleaseSettingController::class, 'create'])->name('new_release_setting.create.post');
	Route::get('new_release/setting/update/{id}', [NewReleaseSettingController::class, 'showUpdate'])->name('new_release_setting.update');
	Route::post('new_release/setting/update', [NewReleaseSettingController::class, 'update'])->name('new_release_setting.update.post');
	Route::post('new_release/setting/delete/{id}', [NewReleaseSettingController::class, 'delete'])->name('new_release_setting.delete');

});


/* sanctum sample 
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum'); */

