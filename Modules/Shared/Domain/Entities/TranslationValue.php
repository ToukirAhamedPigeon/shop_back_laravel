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

    public ?TranslationKey $key = null;

    public function __construct(
        int $id,
        int $keyId,
        string $lang,
        string $value,
        ?DateTimeImmutable $createdAt = null,
        ?TranslationKey $key = null
    ) {
        $this->id = $id;
        $this->keyId = $keyId;
        $this->lang = $lang;
        $this->value = $value;
        $this->createdAt = $createdAt ?? new DateTimeImmutable();
        $this->key = $key;
    }
}
