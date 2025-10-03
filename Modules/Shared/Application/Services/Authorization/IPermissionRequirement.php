<?php

namespace Modules\Shared\Application\Services\Authorization;


interface IPermissionRequirement
{
    /**
     * @return array<string>
     */
    public function getPermissionNames(): array;

    public function getRelation(): string;
}
