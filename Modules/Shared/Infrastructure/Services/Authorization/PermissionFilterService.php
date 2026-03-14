<?php

namespace Modules\Shared\Infrastructure\Services\Authorization;

use Closure;
use Illuminate\Http\Request;
use Modules\Shared\Application\Services\Authorization\IPermissionFilter;
use Modules\Shared\Application\Services\Authorization\IPermissionHandlerService;
use Illuminate\Support\Facades\Log;

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
            Log::warning('Permission check failed: No authenticated user');
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Parse middleware parameters
        $relation = 'or'; // default
        $permissions = [];

        foreach ($params as $param) {
            if ($param === 'any' || $param === 'all') {
                $relation = $param === 'any' ? 'or' : 'and';
            } else {
                $permissions[] = $param;
            }
        }

        Log::info('Checking permissions', [
            'user_id' => $user->id,
            'relation' => $relation,
            'permissions' => $permissions
        ]);

        $requirement = new PermissionRequirement($permissions, $relation);

        if (!$this->permissionService->handle((string) $user->id, $requirement)) {
            Log::warning('Permission denied', [
                'user_id' => $user->id,
                'required' => $permissions
            ]);
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
