<?php

namespace Modules\Shared\Application\Services\Authorization;

interface IPermissionFilter
{
    /**
     * Authorize a user against a set of permissions and relation.
     *
     * @param string $userId
     * @param array<string> $permissions
     * @param string $relation
     *
     * @return bool
     */
    public function authorize(string $userId, array $permissions, string $relation): bool;
}
