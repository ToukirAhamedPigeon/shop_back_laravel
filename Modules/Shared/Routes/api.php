<?php

use Illuminate\Support\Facades\Route;
use Modules\Shared\API\Controllers\AuthController;
use Modules\Shared\API\Controllers\TranslationsController;
use Modules\Shared\API\Controllers\CsrfController;
use Modules\Shared\API\Controllers\PasswordResetController;

Route::prefix('csrf')->middleware('web')->group(function () {
    Route::get('/token', [CsrfController::class, 'getToken']);
});

Route::post('/auth/login', [AuthController::class, 'login']);          // issue access + refresh token
Route::post('/auth/refresh', [AuthController::class, 'refresh']);    // refresh access token using cookie

Route::get('/translations/get', [TranslationsController::class, 'get']);


Route::prefix('auth/password-reset')->group(function () {

    // 1️⃣ Request password reset email
    Route::post('/request', [PasswordResetController::class, 'request'])
        ->middleware('guest:api');

    // 2️⃣ Validate reset token
    Route::get('/validate/{token}', [PasswordResetController::class, 'validateToken'])
        ->middleware('guest:api');

    // 3️⃣ Reset password
    Route::post('/reset', [PasswordResetController::class, 'reset'])
        ->middleware('guest:api');
});
// Protected routes
Route::middleware(['auth:api'])->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/auth/logout-all', [AuthController::class, 'logoutAll']);
    Route::post('/auth/logout-others', [AuthController::class, 'logoutOthers']);
});

