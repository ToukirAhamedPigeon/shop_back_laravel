<?php

namespace Modules\Shared\Infrastructure\Services\Authorization;

use Closure;
use Illuminate\Http\Request;
use Modules\Shared\Application\Services\Authorization\IPermissionFilter;
use Modules\Shared\Application\Services\Authorization\IPermissionHandlerService;

class PermissionFilterService implements IPermissionFilter
{
    protected IPermissionHandlerService $permissionService;

    public function __construct(IPermissionHandlerService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    /**
     * Middleware-style handler to check user permissions.
     */
    public function handle(Request $request, Closure $next, ...$params)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Ensure permissions are an array (comma-separated string allowed)
        $relationString = $params[0] ?? 'any';
        $permissions = $params;
        if (is_array($permissions) && count($permissions) > 1) {
            array_shift($permissions);
            $permissions = array_map('trim', $permissions);
        }
        // Map raw string relation -> enum

        $relation = strtolower($relationString) === 'all' ? 'and' : 'or';
        $requirement = new PermissionRequirement($permissions, $relation);

        if (!$this->permissionService->handle((string) $user->id, $requirement)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return $next($request);
    }

    /**
     * Direct interface-based authorization (without middleware context).
     */
    public function authorize(string $userId, array $permissions, string $relation): bool
    {
        $requirement = new PermissionRequirement($permissions, $relation);
        return $this->permissionService->handle($userId, $requirement);
    }
}
