<?php

namespace Modules\Shared\Domain\Entities;

use DateTimeImmutable;

final class TranslationKey
{
    public int $id;
    public string $key;
    public string $module;
    public DateTimeImmutable $createdAt;

    /** @var TranslationValue[] */
    public array $values = [];

    public function __construct(
        int $id,
        string $key,
        string $module = 'common',
        ?DateTimeImmutable $createdAt = null,
        array $values = []
    ) {
        $this->id = $id;
        $this->key = $key;
        $this->module = $module;
        $this->createdAt = $createdAt ?? new DateTimeImmutable();
        $this->values = $values;
    }
}
