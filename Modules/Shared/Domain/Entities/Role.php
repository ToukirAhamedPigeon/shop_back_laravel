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
    public ?DateTimeImmutable $deletedAt;
    public DateTimeImmutable $createdAt;
    public DateTimeImmutable $updatedAt;
    public ?string $createdBy;
    public ?string $updatedBy;

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
        ?DateTimeImmutable $deletedAt = null,
        ?DateTimeImmutable $createdAt = null,
        ?DateTimeImmutable $updatedAt = null,
        ?string $createdBy = null,
        ?string $updatedBy = null,
        array $rolePermissions = [],
        array $modelRoles = []
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->guardName = $guardName;
        $this->isActive = $isActive;
        $this->isDeleted = $isDeleted;
        $this->deletedAt = $deletedAt;
        $this->createdAt = $createdAt ?? new DateTimeImmutable();
        $this->updatedAt = $updatedAt ?? new DateTimeImmutable();
        $this->createdBy = $createdBy;
        $this->updatedBy = $updatedBy;
        $this->rolePermissions = $rolePermissions;
        $this->modelRoles = $modelRoles;
    }

    public function activate(): void
    {
        $this->isActive = true;
    }

    public function deactivate(): void
    {
        $this->isActive = false;
    }

    public function markDeleted(): void
    {
        $this->isDeleted = true;
        $this->deletedAt = new DateTimeImmutable();
    }

    public function restore(): void
    {
        $this->isDeleted = false;
        $this->deletedAt = null;
    }
}
