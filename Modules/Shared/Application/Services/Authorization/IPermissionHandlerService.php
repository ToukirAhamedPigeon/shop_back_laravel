<?php

namespace Modules\Shared\Application\Services\Authorization;

interface IPermissionHandlerService
{
    /**
     * Checks if the given user satisfies the permission requirement.
     *
     * @param string $userId
     * @param IPermissionRequirement $requirement
     *
     * @return bool
     */
    public function handle(string $userId, IPermissionRequirement $requirement): bool;
}
