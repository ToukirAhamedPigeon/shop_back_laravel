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
    public ?string $ipAddress;
    public ?string $browser;
    public ?string $device;
    public ?string $operatingSystem;
    public ?string $userAgent;

    public function __construct(
        string $id,
        string $actionType,
        string $modelName,
        string $createdBy,
        DateTimeImmutable $createdAt,
        int $createdAtId,
        ?string $detail = null,
        ?string $changes = null,
        ?string $modelId = null,
        ?string $ipAddress = null,
        ?string $browser = null,
        ?string $device = null,
        ?string $operatingSystem = null,
        ?string $userAgent = null
    ) {
        $this->id = $id;
        $this->actionType = $actionType;
        $this->modelName = $modelName;
        $this->createdBy = $createdBy;
        $this->createdAt = $createdAt;
        $this->createdAtId = $createdAtId;
        $this->detail = $detail;
        $this->changes = $changes;
        $this->modelId = $modelId;
        $this->ipAddress = $ipAddress;
        $this->browser = $browser;
        $this->device = $device;
        $this->operatingSystem = $operatingSystem;
        $this->userAgent = $userAgent;
    }
}
