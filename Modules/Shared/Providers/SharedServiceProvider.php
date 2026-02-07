<?php

namespace Modules\Shared\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Config;
use Modules\Shared\Application\Services\IAuthService;
use Modules\Shared\Application\Services\ITranslationService;
use Modules\Shared\Infrastructure\Services\AuthService;
use Modules\Shared\Infrastructure\Services\TranslationService;
use Modules\Shared\Application\Repositories\IUserRepository;
use Modules\Shared\Infrastructure\Repositories\EloquentUserRepository;
use Modules\Shared\Application\Repositories\IRefreshTokenRepository;
use Modules\Shared\Infrastructure\Repositories\EloquentRefreshTokenRepository;
use Modules\Shared\Application\Repositories\IRolePermissionRepository;
use Modules\Shared\Infrastructure\Repositories\EloquentRolePermissionRepository;
use Modules\Shared\Application\Repositories\ITranslationRepository;
use Modules\Shared\Infrastructure\Repositories\EloquentTranslationRepository;
use Modules\Shared\Application\Repositories\IMailRepository;
use Modules\Shared\Infrastructure\Repositories\EloquentMailRepository;
use Modules\Shared\Infrastructure\Services\MailService;
use Modules\Shared\Application\Services\IMailService;
use Modules\Shared\Application\Repositories\IPasswordResetRepository;
use Modules\Shared\Application\Repositories\IUserLogRepository;
use Modules\Shared\Application\Repositories\IUserTableCombinationRepository;
use Modules\Shared\Infrastructure\Repositories\EloquentPasswordResetRepository;
use Modules\Shared\Infrastructure\Services\PasswordResetService;
use Modules\Shared\Application\Services\IPasswordResetService;
use Modules\Shared\Application\Services\IUserLogService;
use Modules\Shared\Application\Services\IUserTableCombinationService;
use Modules\Shared\Infrastructure\Services\UserLogService;
use Modules\Shared\Infrastructure\Repositories\EloquentUserLogRepository;
use Modules\Shared\Infrastructure\Repositories\EloquentUserTableCombinationRepository;
use Modules\Shared\Infrastructure\Services\UserTableCombinationService;
use Modules\Shared\Application\Services\Authorization\IPermissionHandlerService;
use Modules\Shared\Application\Services\IUserService;
use Modules\Shared\Application\Services\UserService;
use Modules\Shared\Infrastructure\Services\Authorization\PermissionHandlerService;
use Modules\Shared\Application\Services\IOptionsService;
use Modules\Shared\Infrastructure\Services\OptionsService;

class SharedServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'Shared';
    protected string $moduleNameLower = 'shared';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        // $this->loadMigrationsFrom(module_path($this->moduleName, 'Database/Migrations'));
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);

        // Bind interfaces to implementations
        $this->app->bind(IAuthService::class, AuthService::class);
        $this->app->bind(IUserRepository::class, EloquentUserRepository::class);
        $this->app->bind(IRefreshTokenRepository::class, EloquentRefreshTokenRepository::class);
        $this->app->bind(IRolePermissionRepository::class, EloquentRolePermissionRepository::class);
        $this->app->bind(IPermissionHandlerService::class, PermissionHandlerService::class);

        // Bind translation repository & service
        $this->app->bind(ITranslationRepository::class, EloquentTranslationRepository::class);
        $this->app->bind(ITranslationService::class, TranslationService::class);

        $this->app->bind(IPasswordResetRepository::class, EloquentPasswordResetRepository::class);
        $this->app->bind(IPasswordResetService::class, PasswordResetService::class);
        $this->app->bind(IMailRepository::class, EloquentMailRepository::class);
        $this->app->bind(IMailService::class, MailService::class);
        $this->app->bind(IUserLogRepository::class, EloquentUserLogRepository::class);
        $this->app->bind(IUserLogService::class, UserLogService::class);
        $this->app->bind(IUserRepository::class, EloquentUserRepository::class);
        $this->app->bind(IUserService::class, UserService::class);
        $this->app->bind(IOptionsService::class, OptionsService::class);
        $this->app->bind(IUserTableCombinationRepository::class, EloquentUserTableCombinationRepository::class);
        $this->app->bind(IUserTableCombinationService::class, UserTableCombinationService::class);
    }

    protected function registerConfig(): void
    {
        $this->publishes([
            module_path($this->moduleName, 'Config/config.php') => config_path($this->moduleNameLower . '.php'),
        ], 'config');

        $this->mergeConfigFrom(
            module_path($this->moduleName, 'Config/config.php'), $this->moduleNameLower
        );
    }

    protected function registerViews(): void
    {
        $viewPath = resource_path('views/modules/' . $this->moduleNameLower);
        $sourcePath = module_path($this->moduleName, 'Resources/views');

        $this->publishes([
            $sourcePath => $viewPath,
        ], ['views', $this->moduleNameLower . '-module-views']);

        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->moduleNameLower);
    }

    protected function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/' . $this->moduleNameLower);

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->moduleNameLower);
        } else {
            $this->loadTranslationsFrom(module_path($this->moduleName, 'Resources/lang'), $this->moduleNameLower);
        }
    }

    public function provides(): array
    {
        return [];
    }

    private function getPublishableViewPaths(): array
    {
        $paths = [];
        foreach (Config::get('view.paths') as $path) {
            if (is_dir($path . '/modules/' . $this->moduleNameLower)) {
                $paths[] = $path . '/modules/' . $this->moduleNameLower;
            }
        }
        return $paths;
    }
}
