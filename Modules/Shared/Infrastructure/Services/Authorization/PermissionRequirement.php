<?php

namespace Modules\Shared\Infrastructure\Services\Authorization;

use Modules\Shared\Application\Services\Authorization\IPermissionRequirement;
use Modules\Shared\Domain\Enums\PermissionRelation;

class PermissionRequirement implements IPermissionRequirement
{
    protected array $permissions;
    protected string $relation;

    public function __construct(array $permissions = [], string $relation = 'or')
    {
        $this->permissions = $permissions;
        $this->relation = $relation;
    }

    public function getPermissionNames(): array
    {
        return $this->permissions;
    }

    public function getRelation(): string
    {
        return $this->relation;
    }
}
