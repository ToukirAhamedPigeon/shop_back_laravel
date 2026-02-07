<?php

namespace Modules\Shared\Application\Responses\Common;

final class SelectOptionResponse
{
    public function __construct(
        public string $value,
        public string $label
    ) {}

    public static function fromArray(array $row): self
    {
        return new self(
            (string) $row['value'],
            (string) $row['label']
        );
    }
}
