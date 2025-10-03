<?php

namespace Modules\Shared\Domain\Entities;

use DateTimeImmutable;

final class ModelPermission
{
    public string $id;
    public string $modelId;
    public ?string $permissionId;
    public string $modelName;
    public DateTimeImmutable $createdAt;
    public DateTimeImmutable $updatedAt;

    public ?Permission $permission = null;

    public function __construct(
        string $id,
        string $modelId,
        ?string $permissionId = null,
        string $modelName = 'User',
        ?DateTimeImmutable $createdAt = null,
        ?DateTimeImmutable $updatedAt = null,
        ?Permission $permission = null
    ) {
        $this->id = $id;
        $this->modelId = $modelId;
        $this->permissionId = $permissionId;
        $this->modelName = $modelName;
        $this->createdAt = $createdAt ?? new DateTimeImmutable();
        $this->updatedAt = $updatedAt ?? new DateTimeImmutable();
        $this->permission = $permission;
    }
}
