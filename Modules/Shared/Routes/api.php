<?php

use Illuminate\Support\Facades\Route;
use Modules\Shared\API\Controllers\AuthController;
use Modules\Shared\API\Controllers\TranslationsController;
use Modules\Shared\API\Controllers\CsrfController;
use Modules\Shared\API\Controllers\PasswordResetController;
use Modules\Shared\API\Controllers\UserController;
use Modules\Shared\API\Controllers\UserLogController;
use Modules\Shared\API\Controllers\UserTableCombinationController;

/*
|--------------------------------------------------------------------------
| CSRF
|--------------------------------------------------------------------------
*/
Route::prefix('csrf')->middleware('web')->group(function () {
    Route::get('/token', [CsrfController::class, 'getToken']);
});

/*
|--------------------------------------------------------------------------
| Auth (Public)
|--------------------------------------------------------------------------
*/
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
});

/*
|--------------------------------------------------------------------------
| Password Reset (Public)
|--------------------------------------------------------------------------
*/
Route::prefix('auth/password-reset')->middleware('guest:api')->group(function () {

    Route::post('/request', [PasswordResetController::class, 'request']);
    Route::get('/validate/{token}', [PasswordResetController::class, 'validateToken']);
    Route::post('/reset', [PasswordResetController::class, 'reset']);
});

/*
|--------------------------------------------------------------------------
| Translations (Public)
|--------------------------------------------------------------------------
*/
Route::get('/translations/get', [TranslationsController::class, 'get']);

/*
|--------------------------------------------------------------------------
| Protected Routes
|--------------------------------------------------------------------------
*/
Route::middleware('auth:api')->group(function () {

    /*
    |-----------------------------
    | Auth
    |-----------------------------
    */
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/auth/logout-all', [AuthController::class, 'logoutAll']);
    Route::post('/auth/logout-others', [AuthController::class, 'logoutOthers']);

    /*
    |-----------------------------
    | User Logs (FULL PARITY)
    |-----------------------------
    */
    Route::prefix('UserLog')->group(function () {
        Route::post('/', [UserLogController::class, 'getFiltered'])->middleware('permission:any,read-admin-dashboard');
        Route::get('/{id}', [UserLogController::class, 'get'])->middleware('permission:any,read-admin-dashboard');
        Route::post('/collections', [UserLogController::class, 'collections'])->middleware('permission:any,read-admin-dashboard');
        Route::post('/action-types', [UserLogController::class, 'actionTypes'])->middleware('permission:any,read-admin-dashboard');
        Route::post('/creators', [UserLogController::class, 'creators'])->middleware('permission:any,read-admin-dashboard');
    });

    Route::prefix('User')->group(function () {

    Route::post(
            '/',
            [UserController::class, 'getFiltered']
        )->middleware('permission:any,read-admin-dashboard');

        Route::get(
            '/{id}',
            [UserController::class, 'get']
        )->middleware('permission:any,read-admin-dashboard');

        Route::post(
            '/collections',
            [UserController::class, 'collections']
        )->middleware('permission:any,read-admin-dashboard');

        Route::post(
            '/roles',
            [UserController::class, 'roles']
        )->middleware('permission:any,read-admin-dashboard');

        Route::post(
            '/creators',
            [UserController::class, 'creators']
        )->middleware('permission:any,read-admin-dashboard');
    });

    /*
    |-----------------------------
    | User Table Combination
    |-----------------------------
    */
    Route::prefix('user-table-combination')
        ->middleware('permission:any,read-admin-dashboard,write-admin-dashboard')
        ->group(function () {
            Route::get('/', [UserTableCombinationController::class, 'get']);
            Route::put('/', [UserTableCombinationController::class, 'update']);
        });
});
