<?php

namespace Modules\Shared\Infrastructure\Services\Authorization;

use Modules\Shared\Application\Services\Authorization\IPermissionHandlerService;
use Modules\Shared\Application\Services\Authorization\IPermissionRequirement;
use Modules\Shared\Infrastructure\Models\EloquentUser;
use Illuminate\Support\Facades\Log;

class PermissionHandlerService implements IPermissionHandlerService
{
    public function handle(string $userId, IPermissionRequirement $requirement): bool
    {
        try {
            $user = EloquentUser::find($userId);

            if (!$user) {
                Log::warning('User not found for permission check', ['user_id' => $userId]);
                return false;
            }

            $requiredPermissions = $requirement->getPermissionNames();

            if (empty($requiredPermissions)) {
                return true; // no permissions required
            }

            Log::info('Checking permissions', [
                'user_id' => $userId,
                'relation' => $requirement->getRelation(),
                'required' => $requiredPermissions,
                'user_roles' => $user->roles->pluck('name')->toArray()
            ]);

            return match ($requirement->getRelation()) {
                'or' => $user->hasAnyPermission($requiredPermissions),
                'and' => $user->hasAllPermissions($requiredPermissions),
                default => false
            };

        } catch (\Exception $e) {
            Log::error('Permission check failed: ' . $e->getMessage(), [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
