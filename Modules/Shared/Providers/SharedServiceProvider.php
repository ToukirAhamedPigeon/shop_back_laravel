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

        // Bind translation repository & service
        $this->app->bind(ITranslationRepository::class, EloquentTranslationRepository::class);
        $this->app->bind(ITranslationService::class, TranslationService::class);
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
