<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\StoreMapController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\NewReleaseSettingController;
use App\Http\Controllers\SalesProductController;
use App\Http\Controllers\PurchaseProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ForgetPasswordController;
use App\Http\Controllers\OethAuthController;
use App\Http\Controllers\AreaManagerController;

use App\Http\Middleware\AuthMiddleware;
use App\Http\Middleware\AccessPermissionMiddleware;
use App\Enums\Functions;

/* use App\Mail\ForgetPassword; */

/* Route::get('/preview-mail', function () {
    return new ForgetPassword('http://laravel.local/forgetPassword/preview'); 
}); */

/***** Oeth fido *****/
Route::get('oeth/auth/redirect', [OethAuthController::class, 'redirect'])->name('oeth.redirect');
Route::get('oeth/auth/callback', [OethAuthController::class, 'callback']);
/***** Oeth fido End *****/

/***** Login *****/
Route::get('/', [AuthController::class, 'showSignin'])->name('signin');
Route::redirect('signin', '/'); 
Route::post('signin', [AuthController::class, 'signin'])->name('signin.post');
Route::get('signout', [AuthController::class, 'signout'])->name('signout');

/***** Forget Password *****/
Route::post('forgetPassword/send', [ForgetPasswordController::class, 'sendLink'])->name('forgetPassword.send');
Route::get('forgetPassword/setting/{token}', [ForgetPasswordController::class, 'showSetting'])->name('forgetPassword.setting');
Route::post('forgetPassword/setting', [ForgetPasswordController::class, 'setting'])->name('forgetPassword.setting.post');

Route::middleware([AuthMiddleware::class])->group(function(){
	/***** Home *****/
	Route::get('home', [HomeController::class, 'index'])->name('home');
	
	/***** User profile *****/
	Route::post('profile/update', [ProfileController::class, 'update'])->name('profile.update.post');
	
	
	/***** 督導維護 *****/
	Route::middleware([AccessPermissionMiddleware::class . Str::start(Functions::AREA_MANAGER->value, ':')])->group(function(){
		Route::get('area_manager', [AreaManagerController::class, 'showSearch'])->name('area_manager');
		Route::post('area_manager/search', [AreaManagerController::class, 'search'])->name('area_manager.search');
		Route::get('area_manager/export/{token}', [AreaManagerController::class, 'export'])->name('area_manager.export');
		Route::post('area_manager/update', [AreaManagerController::class, 'update'])->name('area_manager.update');
	});
	
	
	/***** 產品設定 *****/
	Route::middleware([AccessPermissionMiddleware::class . Str::start(Functions::PRODUCT->value, ':')])->group(function(){
		Route::get('product', [ProductController::class, 'list'])->name('products');
		Route::get('product/list', [ProductController::class, 'list'])->name('product.list');
		Route::get('product/create', [ProductController::class, 'showCreate'])->name('product.create');
		Route::post('product/create', [ProductController::class, 'create'])->name('product.create.post');
		Route::get('product/update/{id}', [ProductController::class, 'showUpdate'])->name('product.update');
		Route::post('product/update', [ProductController::class, 'update'])->name('product.update.post');
		Route::post('product/delete/{id}', [ProductController::class, 'delete'])->name('product.delete');
	});
	
	/***** 新品設定 *****/
	Route::middleware([AccessPermissionMiddleware::class . Str::start(Functions::NEW_RELEASE_SETTING->value, ':')])->group(function(){
		Route::get('new_release/setting', [NewReleaseSettingController::class, 'list'])->name('new_release_setting');
		Route::get('new_release/setting/list', [NewReleaseSettingController::class, 'list'])->name('new_release_setting.list');
		Route::get('new_release/setting/create', [NewReleaseSettingController::class, 'showCreate'])->name('new_release_setting.create');
		Route::post('new_release/setting/create', [NewReleaseSettingController::class, 'create'])->name('new_release_setting.create.post');
		Route::get('new_release/setting/update/{id}', [NewReleaseSettingController::class, 'showUpdate'])->name('new_release_setting.update');
		Route::post('new_release/setting/update', [NewReleaseSettingController::class, 'update'])->name('new_release_setting.update.post');
		Route::post('new_release/setting/delete/{id}', [NewReleaseSettingController::class, 'delete'])->name('new_release_setting.delete');
	});
	
	/***** 銷售設定 *****/
	/* Route::middleware([AccessPermissionMiddleware::class . Str::start(Functions::SALES_SETTING->value, ':')])->group(function(){
		Route::get('sales/setting', [SalesSettingController::class, 'list'])->name('sales_setting');
		Route::get('sales/setting/list', [SalesSettingController::class, 'list'])->name('sales_setting.list');
		Route::get('sales/setting/create', [SalesSettingController::class, 'showCreate'])->name('sales_setting.create');
		Route::post('sales/setting/create', [SalesSettingController::class, 'create'])->name('sales_setting.create.post');
		Route::get('sales/setting/update/{id}', [SalesSettingController::class, 'showUpdate'])->name('sales_setting.update');
		Route::post('sales/setting/update', [SalesSettingController::class, 'update'])->name('sales_setting.update.post');
		Route::post('sales/setting/delete/{id}', [SalesSettingController::class, 'delete'])->name('sales_setting.delete');
	}); */
	
	/***** 銷售產品設定 *****/
	Route::middleware([AccessPermissionMiddleware::class . Str::start(Functions::SALES_PRODUCT->value, ':')])->group(function(){
		Route::get('sales/product', [SalesProductController::class, 'list'])->name('sales_product');
		Route::get('sales/product/list', [SalesProductController::class, 'list'])->name('sales_product.list');
		Route::get('sales/product/setting', [SalesProductController::class, 'showUpdate'])->name('sales_product.update');
		Route::post('sales/product/setting', [SalesProductController::class, 'update'])->name('sales_product.update.post');
	});
	
	/***** 訂貨產品設定 *****/
	Route::middleware([AccessPermissionMiddleware::class . Str::start(Functions::PURCHASE_PRODUCT->value, ':')])->group(function(){
		Route::get('purchase/product', [PurchaseProductController::class, 'list'])->name('purchase_product');
		Route::get('purchase/product/list', [PurchaseProductController::class, 'list'])->name('purchase_product.list');
		Route::get('purchase/product/setting', [PurchaseProductController::class, 'showUpdate'])->name('purchase_product.update');
		Route::post('purchase/product/setting', [PurchaseProductController::class, 'update'])->name('purchase_product.update.post');
	});
	
	/***** 新權限管理(整併為一個功能) *****/
	Route::middleware([AccessPermissionMiddleware::class . Str::start(Functions::USER->value, ':')])->group(function(){
		Route::get('user', [UserController::class, 'list'])->name('users');
		Route::get('user/list', [UserController::class, 'list'])->name('user.list');
		Route::post('user/search', [UserController::class, 'search'])->name('user.search');
		Route::get('user/create', [UserController::class, 'showCreate'])->name('user.create');
		Route::post('user/create', [UserController::class, 'create'])->name('user.create.post');
		Route::get('user/update/{id}', [UserController::class, 'showUpdate'])->name('user.update');
		Route::post('user/update', [UserController::class, 'update'])->name('user.update.post');
		Route::post('user/delete/{id}', [UserController::class, 'delete'])->name('user.delete');
	});
	
	/***** 身份管理 *****
	Route::middleware([AccessPermissionMiddleware::class . Str::start(Functions::ROLE->value, ':')])->group(function(){
		Route::get('role', [RoleController::class, 'list'])->name('roles');
		Route::get('role/list', [RoleController::class, 'list'])->name('role.list');
		Route::get('role/create', [RoleController::class, 'showCreate'])->name('role.create');
		Route::post('role/create', [RoleController::class, 'create'])->name('role.create.post');
		Route::get('role/update/{id}', [RoleController::class, 'showUpdate'])->name('role.update');
		Route::post('role/update', [RoleController::class, 'update'])->name('role.update.post');
		Route::post('role/delete/{id}', [RoleController::class, 'delete'])->name('role.delete');
	});*/

});


