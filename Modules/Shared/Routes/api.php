<?php

use Illuminate\Support\Facades\Route;
use Modules\Shared\API\Controllers\AuthController;
use Modules\Shared\API\Controllers\CommonController;
use Modules\Shared\API\Controllers\TranslationsController;
use Modules\Shared\API\Controllers\CsrfController;
use Modules\Shared\API\Controllers\OptionsController;
use Modules\Shared\API\Controllers\PasswordResetController;
use Modules\Shared\API\Controllers\UserController;
use Modules\Shared\API\Controllers\UserLogController;
use Modules\Shared\API\Controllers\UserTableCombinationController;

/*
|--------------------------------------------------------------------------
| CSRF (Public)
|--------------------------------------------------------------------------
*/
Route::prefix('csrf')->middleware('web')->group(function () {
    Route::get('/token', [CsrfController::class, 'getToken'])->name('csrf.token');
});

/*
|--------------------------------------------------------------------------
| Auth (Public)
|--------------------------------------------------------------------------
*/
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->name('auth.login');
    Route::post('/refresh', [AuthController::class, 'refresh'])->name('auth.refresh');
});

/*
|--------------------------------------------------------------------------
| Password Reset (Public)
|--------------------------------------------------------------------------
*/
Route::prefix('auth/password-reset')->group(function () {
    Route::post('/request', [PasswordResetController::class, 'request'])->name('password.reset.request');
    Route::get('/validate/{token}', [PasswordResetController::class, 'validateToken'])->name('password.reset.validate');
    Route::post('/reset', [PasswordResetController::class, 'reset'])->name('password.reset');

    // Password Change (Public with token)
    Route::post('/change-password/verify', [PasswordResetController::class, 'verifyPasswordChange'])->name('password.change.verify');
    Route::get('/change-password/validate/{token}', [PasswordResetController::class, 'validateChangeToken'])->name('password.change.validate');
});

/*
|--------------------------------------------------------------------------
| Translations (Public)
|--------------------------------------------------------------------------
*/
Route::get('/translations/get', [TranslationsController::class, 'get'])->name('translations.get');

/*
|--------------------------------------------------------------------------
| User Email Verification (Public)
|--------------------------------------------------------------------------
*/
Route::get('/users/verify-email', [UserController::class, 'verifyEmail'])->name('verification.verify');

/*
|--------------------------------------------------------------------------
| Protected Routes (Require Authentication)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:api')->group(function () {

    /*
    |-----------------------------
    | Auth (Authenticated)
    |-----------------------------
    */
    Route::prefix('auth')->group(function () {
        Route::get('/me', [AuthController::class, 'me'])->name('auth.me');
        Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::post('/logout-all', [AuthController::class, 'logoutAll'])->name('auth.logout-all');
        Route::post('/logout-others', [AuthController::class, 'logoutOthers'])->name('auth.logout-others');
    });

    /*
    |-----------------------------
    | Password Reset (Authenticated - Change Password)
    |-----------------------------
    */
    Route::prefix('auth/password-reset')->group(function () {
        Route::post('/change-password/request', [PasswordResetController::class, 'requestPasswordChange'])->name('password.change.request');
    });

    /*
    |-----------------------------
    | User Profile (Self - Authenticated only)
    |-----------------------------
    */
    Route::prefix('users')->group(function () {
        Route::get('/profile', [UserController::class, 'getProfile'])->name('user.profile');
        Route::put('/profile', [UserController::class, 'updateProfile'])->name('user.profile.update');
    });

    /*
    |-----------------------------
    | Admin Routes (require read-admin-dashboard permission)
    |-----------------------------
    */
    Route::middleware('permission:any,read-admin-dashboard')->group(function () {

        /*
        |-----------------------------
        | User Logs (Admin)
        |-----------------------------
        */
        Route::prefix('UserLog')->group(function () {
            Route::post('/', [UserLogController::class, 'getFiltered'])->name('userlog.filtered');
            Route::get('/{id}', [UserLogController::class, 'get'])->name('userlog.get');
            Route::post('/collections', [UserLogController::class, 'collections'])->name('userlog.collections');
            Route::post('/action-types', [UserLogController::class, 'actionTypes'])->name('userlog.action-types');
            Route::post('/creators', [UserLogController::class, 'creators'])->name('userlog.creators');
        });

        /*
        |-----------------------------
        | Users (Admin)
        |-----------------------------
        */
        Route::prefix('User')->group(function () {
            Route::post('/', [UserController::class, 'getUsers'])->name('users.list');
            Route::get('/{id}', [UserController::class, 'getUser'])->name('users.get');
            Route::post('/create', [UserController::class, 'create'])->name('users.create');
            Route::post('/{id}/resend-verification', [UserController::class, 'resendVerification'])->name('users.resend-verification');
            Route::post('/{id}/regenerate-qr', [UserController::class, 'regenerateQr'])->name('users.regenerate-qr');
            Route::get('/{id}/edit', [UserController::class, 'getUserForEdit'])->name('users.edit');
            Route::put('/{id}', [UserController::class, 'update'])->name('users.update');
            Route::delete('/{id}', [UserController::class, 'deleteUser'])->name('users.delete');
            Route::post('/{id}/restore', [UserController::class, 'restoreUser'])->name('users.restore');
            Route::get('/{id}/delete-info', [UserController::class, 'getDeleteInfo'])->name('users.delete-info');
        });

        /*
        |-----------------------------
        | Options (Admin)
        |-----------------------------
        */
        Route::prefix('Options')->group(function () {
            Route::post('/{type}', [OptionsController::class, 'getOptions'])->name('options.get');
        });

        /*
        |-----------------------------
        | Common (Admin)
        |-----------------------------
        */
        Route::prefix('common')->group(function () {
            Route::post('/check-unique', [CommonController::class, 'checkUnique'])->name('common.check-unique');
        });

        /*
        |-----------------------------
        | User Table Combination (Admin - requires both permissions)
        |-----------------------------
        */
        Route::prefix('user-table-combination')
            ->middleware('permission:any,read-admin-dashboard,write-admin-dashboard')
            ->group(function () {
                Route::get('/', [UserTableCombinationController::class, 'get'])->name('user-table.get');
                Route::put('/', [UserTableCombinationController::class, 'update'])->name('user-table.update');
            });
    });
});
