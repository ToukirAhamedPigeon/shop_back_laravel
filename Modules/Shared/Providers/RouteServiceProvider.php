<?php

namespace Modules\Shared\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    protected $moduleNamespace = 'Modules\Shared\API\Controllers';

    public function boot()
    {
        parent::boot();
    }

    public function map()
    {
        $this->mapApiRoutes();
    }

    protected function mapApiRoutes()
    {
        Route::prefix('api')
            ->middleware(['api']) // stateless API, good for Passport
            ->namespace($this->moduleNamespace)
            ->group(module_path('Shared', '/Routes/api.php'));
    }
}
