<?php

use Illuminate\Support\Facades\Route;
use Modules\Shared\API\Controllers\AuthController;
use Modules\Shared\API\Controllers\TranslationsController;
use Modules\Shared\API\Controllers\CsrfController;

Route::prefix('csrf')->middleware('web')->group(function () {
    Route::get('/token', [CsrfController::class, 'getToken']);
});

Route::post('/auth/login', [AuthController::class, 'login']);          // issue access + refresh token
Route::post('/auth/refresh', [AuthController::class, 'refresh']);    // refresh access token using cookie

Route::get('/translations/get', [TranslationsController::class, 'get']);

// Protected routes
Route::middleware(['auth:api'])->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/auth/logout-all', [AuthController::class, 'logoutAll']);
    Route::post('/auth/logout-others', [AuthController::class, 'logoutOthers']);
});

