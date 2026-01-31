<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Shared\Infrastructure\Services\Authorization\PermissionFilterService;

class PermissionMiddleware
{
    public function __construct(
        protected PermissionFilterService $permissionFilter
    ) {}

    public function handle(Request $request, Closure $next, ...$params)
    {
        return $this->permissionFilter->handle($request, $next, ...$params);
    }
}
