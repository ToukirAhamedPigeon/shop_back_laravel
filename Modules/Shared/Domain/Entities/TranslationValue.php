<?php

namespace Modules\Shared\Domain\Entities;

use DateTimeImmutable;

final class TranslationValue
{
    public int $id;
    public int $keyId;
    public string $lang;
    public string $value;
    public DateTimeImmutable $createdAt;
    public ?DateTimeImmutable $updatedAt;

    public ?TranslationKey $key = null;

    public function __construct(
        int $id,
        int $keyId,
        string $lang,
        string $value,
        ?DateTimeImmutable $createdAt = null,
        ?DateTimeImmutable $updatedAt = null,
        ?TranslationKey $key = null
    ) {
        $this->id = $id;
        $this->keyId = $keyId;
        $this->lang = $lang;
        $this->value = $value;
        $this->createdAt = $createdAt ?? new DateTimeImmutable();
        $this->updatedAt = $updatedAt;
        $this->key = $key;
    }

    public function updateValue(string $value): void
    {
        $this->value = $value;
        $this->updatedAt = new DateTimeImmutable();
    }
}
