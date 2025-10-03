<?php

namespace Modules\Shared\Application\Services\Authorization;


interface IPermissionAttribute
{
    /**
     * Get the permissions required for this attribute.
     *
     * @return array<string>
     */
    public function getPermissions(): array;

    /**
     * Defines whether all permissions are required (AND)
     * or at least one (OR).
     */
    public function getRelation(): string;
}
