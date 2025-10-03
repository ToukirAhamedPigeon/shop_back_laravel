<?php

namespace Modules\Shared\Domain\Entities;

use DateTimeImmutable;

final class RolePermission
{
    public string $id;
    public ?string $permissionId;
    public ?string $roleId;
    public DateTimeImmutable $createdAt;
    public DateTimeImmutable $updatedAt;

    public ?Permission $permission = null;
    public ?Role $role = null;

    public function __construct(
        string $id,
        ?string $permissionId = null,
        ?string $roleId = null,
        ?DateTimeImmutable $createdAt = null,
        ?DateTimeImmutable $updatedAt = null,
        ?Permission $permission = null,
        ?Role $role = null
    ) {
        $this->id = $id;
        $this->permissionId = $permissionId;
        $this->roleId = $roleId;
        $this->createdAt = $createdAt ?? new DateTimeImmutable();
        $this->updatedAt = $updatedAt ?? new DateTimeImmutable();
        $this->permission = $permission;
        $this->role = $role;
    }
}
