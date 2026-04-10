<?php

/*
After updating the routes, please run:
php artisan docs:generate
*/
use Illuminate\Support\Facades\Route;
use Modules\Shared\API\Controllers\AuthController;
use Modules\Shared\API\Controllers\CommonController;
use Modules\Shared\API\Controllers\PermissionController;
use Modules\Shared\API\Controllers\TranslationsController;
use Modules\Shared\API\Controllers\CsrfController;
use Modules\Shared\API\Controllers\OptionsController;
use Modules\Shared\API\Controllers\PasswordResetController;
use Modules\Shared\API\Controllers\RoleController;
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
        Route::get('/me', [AuthController::class, 'me'])->name('auth.me')->middleware('permission:any,read-admin-auth');
        Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout')->middleware('permission:any,logout-admin-auth');
        Route::post('/logout-all', [AuthController::class, 'logoutAll'])->name('auth.logout-all')->middleware('permission:any,logout-all-admin-auth');
        Route::post('/logout-others', [AuthController::class, 'logoutOthers'])->name('auth.logout-others')->middleware('permission:any,logout-others-admin-auth');
    });

    /*
    |-----------------------------
    | Password Reset (Authenticated - Change Password)
    |-----------------------------
    */
    Route::prefix('auth/password-reset')->group(function () {
        Route::post('/change-password/request', [PasswordResetController::class, 'requestPasswordChange'])->name('password.change.request')->middleware('permission:any,change-admin-password');
    });

    /*
    |-----------------------------
    | User Profile (Self - Authenticated only)
    |-----------------------------
    */
    Route::prefix('users')->group(function () {
        Route::get('/profile', [UserController::class, 'getProfile'])->name('user.profile')->middleware('permission:any,read-admin-profile');
        Route::put('/profile', [UserController::class, 'updateProfile'])->name('user.profile.update')->middleware(['parse.multipart','permission:any,update-admin-profile']);
    });

    /*
    |-----------------------------
    | Admin Routes (require read-admin-dashboard permission)
    |-----------------------------
    */
    // Route::middleware('permission:any,read-admin-dashboard')->group(function () {
    /*
    |-----------------------------
    | Roles (Admin)
    |-----------------------------
    */
    Route::prefix('roles')->group(function () {
        Route::post('/', [RoleController::class, 'getRoles'])->name('roles.list')->middleware('permission:any,read-admin-roles');
        Route::get('/{id}', [RoleController::class, 'getRole'])->name('roles.get')->middleware('permission:any,read-admin-roles');
        Route::get('/{id}/edit', [RoleController::class, 'getRoleForEdit'])->name('roles.edit')->middleware('permission:any,update-admin-roles');
        Route::post('/create', [RoleController::class, 'create'])->name('roles.create')->middleware('permission:any,create-admin-roles');
        Route::put('/{id}', [RoleController::class, 'update'])->name('roles.update')->middleware('permission:any,update-admin-roles');
        Route::delete('/{id}', [RoleController::class, 'deleteRole'])->name('roles.delete')->middleware('permission:any,delete-admin-roles');
        Route::post('/{id}/restore', [RoleController::class, 'restoreRole'])->name('roles.restore')->middleware('permission:any,restore-admin-roles');
        Route::get('/{id}/delete-info', [RoleController::class, 'getDeleteInfo'])->name('roles.delete-info')->middleware('permission:any,restore-admin-roles');
    });

    /*
    |-----------------------------
    | Permissions (Admin)
    |-----------------------------
    */
    Route::prefix('permissions')->group(function () {
        Route::post('/', [PermissionController::class, 'getPermissions'])->name('permissions.list')->middleware('permission:any,read-admin-permissions');
        Route::get('/{id}', [PermissionController::class, 'getPermission'])->name('permissions.get')->middleware('permission:any,read-admin-permissions');
        Route::get('/{id}/edit', [PermissionController::class, 'getPermissionForEdit'])->name('permissions.edit')->middleware('permission:any,update-admin-permissions');
        Route::post('/create', [PermissionController::class, 'create'])->name('permissions.create')->middleware('permission:any,create-admin-permissions');
        Route::put('/{id}', [PermissionController::class, 'update'])->name('permissions.update')->middleware('permission:any,update-admin-permissions');
        Route::delete('/{id}', [PermissionController::class, 'deletePermission'])->name('permissions.delete')->middleware('permission:any,delete-admin-permissions');
        Route::post('/{id}/restore', [PermissionController::class, 'restorePermission'])->name('permissions.restore')->middleware('permission:any,restore-admin-permissions');
        Route::get('/{id}/delete-info', [PermissionController::class, 'getDeleteInfo'])->name('permissions.delete-info')->middleware('permission:any,restore-admin-permissions');
    });

    /*
    |-----------------------------
    | User Logs (Admin)
    |-----------------------------
    */
    Route::prefix('UserLog')->group(function () {
        Route::post('/', [UserLogController::class, 'getFiltered'])->name('userlog.filtered')->middleware('permission:any,read-admin-user-logs');
        Route::get('/{id}', [UserLogController::class, 'get'])->name('userlog.get')->middleware('permission:any,read-admin-user-logs');
        Route::post('/collections', [UserLogController::class, 'collections'])->name('userlog.collections')->middleware('permission:any,read-admin-user-logs');
        Route::post('/action-types', [UserLogController::class, 'actionTypes'])->name('userlog.action-types')->middleware('permission:any,read-admin-user-logs');
        Route::post('/creators', [UserLogController::class, 'creators'])->name('userlog.creators')->middleware('permission:any,read-admin-user-logs');
    });

    /*
    |-----------------------------
    | Users (Admin)
    |-----------------------------
    */
    Route::prefix('users')->group(function () {
        Route::post('/', [UserController::class, 'getUsers'])->name('users.list')->middleware('permission:any,read-admin-users');
        Route::get('/{id}', [UserController::class, 'getUser'])->name('users.get')->middleware('permission:any,read-admin-users');
        Route::post('/create', [UserController::class, 'create'])->name('users.create')->middleware(['permission:any,create-admin-users', 'parse.multipart']);
        Route::post('/{id}/resend-verification', [UserController::class, 'resendVerification'])->name('users.resend-verification')->middleware('permission:any,create-admin-users');
        Route::post('/{id}/regenerate-qr', [UserController::class, 'regenerateQr'])->name('users.regenerate-qr')->middleware('permission:any,update-admin-users');
        Route::get('/{id}/edit', [UserController::class, 'getUserForEdit'])->name('users.edit')->middleware('permission:any,update-admin-users');
        Route::put('/{id}', [UserController::class, 'update'])->name('users.update')->middleware(['permission:any,update-admin-users', 'parse.multipart']);
        Route::delete('/{id}', [UserController::class, 'deleteUser'])->name('users.delete')->middleware('permission:any,delete-admin-users');
        Route::post('/{id}/restore', [UserController::class, 'restoreUser'])->name('users.restore')->middleware('permission:any,restore-admin-users');
        Route::get('/{id}/delete-info', [UserController::class, 'getDeleteInfo'])->name('users.delete-info')->middleware('permission:any,restore-admin-users');
    });

    /*
    |-----------------------------
    | Options (Admin)
    |-----------------------------
    */
    Route::prefix('Options')->group(function () {
        Route::post('/{type}', [OptionsController::class, 'getOptions'])->name('options.get')->middleware('permission:any,read-admin-options');
    });

    /*
    |-----------------------------
    | Common (Admin)
    |-----------------------------
    */
    Route::prefix('common')->group(function () {
        Route::post('/check-unique', [CommonController::class, 'checkUnique'])->name('common.check-unique')->middleware('permission:any,check-admin-unique');
    });

    /*
    |-----------------------------
    | User Table Combination (Admin - requires both permissions)
    |-----------------------------
    */
    Route::prefix('user-table-combination')
        ->middleware('permission:any,read-admin-user-table-combination,update-admin-user-table-combination')
        ->group(function () {
            Route::get('/', [UserTableCombinationController::class, 'get'])->name('user-table.get')->middleware('permission:any,read-admin-user-table-combination');
            Route::put('/', [UserTableCombinationController::class, 'update'])->name('user-table.update')->middleware('permission:any,read-admin-user-table-combination');
        });

    /*
    |-----------------------------
    | Translations Admin (CRUD)
    |-----------------------------
    */
    Route::prefix('translations')->group(function () {
        Route::post('/list', [TranslationsController::class, 'getTranslations'])
            ->name('translations.list')
            ->middleware('permission:any,read-admin-translations');

        Route::get('/{id}', [TranslationsController::class, 'getTranslation'])
            ->name('translations.get')
            ->middleware('permission:any,read-admin-translations');

        Route::get('/{id}/edit', [TranslationsController::class, 'getTranslationForEdit'])
            ->name('translations.edit')
            ->middleware('permission:any,update-admin-translations');

        Route::post('/create', [TranslationsController::class, 'createTranslation'])
            ->name('translations.create')
            ->middleware('permission:any,create-admin-translations');

        Route::put('/{id}', [TranslationsController::class, 'updateTranslation'])
            ->name('translations.update')
            ->middleware('permission:any,update-admin-translations');

        Route::delete('/{id}', [TranslationsController::class, 'deleteTranslation'])
            ->name('translations.delete')
            ->middleware('permission:any,delete-admin-translations');

        Route::get('/modules', [TranslationsController::class, 'getModules'])
            ->name('translations.modules')
            ->middleware('permission:any,read-admin-translations');
    });

    Route::prefix('options')->group(function () {
        Route::post('/list', [OptionsController::class, 'getOptionsList'])
            ->name('options.list')
            ->middleware('permission:any,read-admin-options');

        Route::get('/{id}', [OptionsController::class, 'getOption'])
            ->name('options.get')
            ->middleware('permission:any,read-admin-options');

        Route::get('/{id}/edit', [OptionsController::class, 'getOptionForEdit'])
            ->name('options.edit')
            ->middleware('permission:any,update-admin-options');

        Route::post('/create', [OptionsController::class, 'create'])
            ->name('options.create')
            ->middleware('permission:any,create-admin-options');

        Route::put('/{id}', [OptionsController::class, 'update'])
            ->name('options.update')
            ->middleware('permission:any,update-admin-options');

        Route::delete('/{id}', [OptionsController::class, 'deleteOption'])
            ->name('options.delete')
            ->middleware('permission:any,delete-admin-options');

        Route::post('/{id}/restore', [OptionsController::class, 'restoreOption'])
            ->name('options.restore')
            ->middleware('permission:any,update-admin-options');

        Route::get('/{id}/delete-info', [OptionsController::class, 'getDeleteInfo'])
            ->name('options.delete-info')
            ->middleware('permission:any,delete-admin-options');

        Route::post('/parents', [OptionsController::class, 'getParentOptions'])
            ->name('options.parents')
            ->middleware('permission:any,read-admin-options');
    });
    // });
});
