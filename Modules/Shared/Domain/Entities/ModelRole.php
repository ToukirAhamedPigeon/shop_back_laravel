<?php

namespace Modules\Shared\Domain\Entities;

use DateTimeImmutable;

final class ModelRole
{
    public string $id;
    public string $modelId;
    public string $roleId;
    public string $modelName;
    public DateTimeImmutable $createdAt;
    public DateTimeImmutable $updatedAt;

    public ?Role $role = null;

    public function __construct(
        string $id,
        string $modelId,
        string $roleId,
        string $modelName = 'User',
        ?DateTimeImmutable $createdAt = null,
        ?DateTimeImmutable $updatedAt = null,
        ?Role $role = null
    ) {
        $this->id = $id;
        $this->modelId = $modelId;
        $this->roleId = $roleId;
        $this->modelName = $modelName;
        $this->createdAt = $createdAt ?? new DateTimeImmutable();
        $this->updatedAt = $updatedAt ?? new DateTimeImmutable();
        $this->role = $role;
    }
}
