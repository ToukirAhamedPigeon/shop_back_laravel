<?php

namespace Modules\Shared\Domain\Entities;

use DateTimeImmutable;

final class Role
{
    public string $id;
    public string $name;
    public string $guardName;
    public bool $isActive;
    public bool $isDeleted;
    public DateTimeImmutable $createdAt;
    public DateTimeImmutable $updatedAt;

    /** @var RolePermission[] */
    public array $rolePermissions = [];

    /** @var ModelRole[] */
    public array $modelRoles = [];

    public function __construct(
        string $id,
        string $name,
        string $guardName = 'admin',
        bool $isActive = true,
        bool $isDeleted = false,
        ?DateTimeImmutable $createdAt = null,
        ?DateTimeImmutable $updatedAt = null
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->guardName = $guardName;
        $this->isActive = $isActive;
        $this->isDeleted = $isDeleted;
        $this->createdAt = $createdAt ?? new DateTimeImmutable();
        $this->updatedAt = $updatedAt ?? new DateTimeImmutable();
    }
}
