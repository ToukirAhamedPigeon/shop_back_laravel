<?php

namespace Modules\Shared\Domain\Entities;

use DateTimeImmutable;

final class UserLog
{
    public string $id;
    public ?string $detail;
    public ?string $changes;
    public string $actionType;
    public string $modelName;
    public ?string $modelId;
    public string $createdBy;
    public DateTimeImmutable $createdAt;
    public int $createdAtId;

    public function __construct(
        string $id,
        string $actionType,
        string $modelName,
        string $createdBy,
        int $createdAtId,
        ?string $detail = null,
        ?string $changes = null,
        ?string $modelId = null,
        ?DateTimeImmutable $createdAt = null
    ) {
        $this->id = $id;
        $this->detail = $detail;
        $this->changes = $changes;
        $this->actionType = $actionType;
        $this->modelName = $modelName;
        $this->modelId = $modelId;
        $this->createdBy = $createdBy;
        $this->createdAt = $createdAt ?? new DateTimeImmutable();
        $this->createdAtId = $createdAtId;
    }
}
