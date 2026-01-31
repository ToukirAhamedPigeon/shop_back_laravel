<?php

namespace Modules\Shared\Infrastructure\Services\Authorization;

use Modules\Shared\Application\Services\Authorization\IPermissionHandlerService;
use Modules\Shared\Application\Services\Authorization\IPermissionRequirement;
use Modules\Shared\Infrastructure\Models\EloquentUser;

class PermissionHandlerService implements IPermissionHandlerService
{
    public function handle(string $userId, IPermissionRequirement $requirement): bool
    {
        $user = EloquentUser::find($userId);

        if (!$user) {
            return false;
        }

        $permissions = $requirement->getPermissionNames();

        if (empty($permissions)) {
            return true; // no permissions required
        }

        return match ($requirement->getRelation()) {
            'or' => $user->hasAnyPermission($permissions),
            'and' => $user->hasAllPermissions($permissions),
        };
    }
}
