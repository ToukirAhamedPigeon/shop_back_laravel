<?php

namespace Modules\Shared\Domain\Entities;

use DateTimeImmutable;

final class Option
{
    public string $id;
    public string $name;
    public ?string $parentId;
    public bool $hasChild;
    public bool $isActive;
    public bool $isDeleted;
    public ?DateTimeImmutable $deletedAt;
    public DateTimeImmutable $createdAt;
    public DateTimeImmutable $updatedAt;
    public ?string $createdBy;
    public ?string $updatedBy;
    public ?string $deletedBy;

    /** @var Option[] */
    public array $children = [];

    public ?Option $parent = null;

    public function __construct(
        string $id,
        string $name,
        ?string $parentId = null,
        bool $hasChild = false,
        bool $isActive = true,
        bool $isDeleted = false,
        ?DateTimeImmutable $deletedAt = null,
        ?DateTimeImmutable $createdAt = null,
        ?DateTimeImmutable $updatedAt = null,
        ?string $createdBy = null,
        ?string $updatedBy = null,
        ?string $deletedBy = null
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->parentId = $parentId;
        $this->hasChild = $hasChild;
        $this->isActive = $isActive;
        $this->isDeleted = $isDeleted;
        $this->deletedAt = $deletedAt;
        $this->createdAt = $createdAt ?? new DateTimeImmutable();
        $this->updatedAt = $updatedAt ?? new DateTimeImmutable();
        $this->createdBy = $createdBy;
        $this->updatedBy = $updatedBy;
        $this->deletedBy = $deletedBy;
    }

    public function activate(): void
    {
        $this->isActive = true;
    }

    public function deactivate(): void
    {
        $this->isActive = false;
    }

    public function markDeleted(?string $deletedBy = null): void
    {
        $this->isDeleted = true;
        $this->deletedAt = new DateTimeImmutable();
        $this->deletedBy = $deletedBy;
    }

    public function restore(): void
    {
        $this->isDeleted = false;
        $this->deletedAt = null;
        $this->deletedBy = null;
    }
}
