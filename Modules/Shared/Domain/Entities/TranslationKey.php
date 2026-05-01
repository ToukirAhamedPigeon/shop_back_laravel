<?php

namespace Modules\Shared\Domain\Entities;

use DateTimeImmutable;

final class TranslationKey
{
    public int $id;
    public string $key;
    public string $module;
    public DateTimeImmutable $createdAt;
    public ?DateTimeImmutable $updatedAt;
    public ?string $createdBy;
    public ?string $updatedBy;

    /** @var TranslationValue[] */
    public array $values = [];

    public function __construct(
        int $id,
        string $key,
        string $module = 'common',
        ?DateTimeImmutable $createdAt = null,
        ?DateTimeImmutable $updatedAt = null,
        ?string $createdBy = null,
        ?string $updatedBy = null,
        array $values = []
    ) {
        $this->id = $id;
        $this->key = $key;
        $this->module = $module;
        $this->createdAt = $createdAt ?? new DateTimeImmutable();
        $this->updatedAt = $updatedAt;
        $this->createdBy = $createdBy;
        $this->updatedBy = $updatedBy;
        $this->values = $values;
    }

    public function update(string $key, string $module, ?string $updatedBy = null): void
    {
        $this->key = $key;
        $this->module = $module;
        $this->updatedAt = new DateTimeImmutable();
        $this->updatedBy = $updatedBy;
    }
}
