<?php

namespace Modules\Shared\Domain\Entities;

use DateTimeImmutable;

final class UserTableCombination
{
    public string $id;
    public string $tableId;
    public array $showColumnCombinations;
    public string $userId;
    public ?string $updatedBy;
    public DateTimeImmutable $updatedAt;

    public function __construct(
        string $id,
        string $tableId,
        array $showColumnCombinations,
        string $userId,
        ?string $updatedBy = null,
        ?DateTimeImmutable $updatedAt = null
    ) {
        $this->id = $id;
        $this->tableId = $tableId;
        $this->showColumnCombinations = $showColumnCombinations;
        $this->userId = $userId;
        $this->updatedBy = $updatedBy;
        $this->updatedAt = $updatedAt ?? new DateTimeImmutable();
    }
}
