<?php

namespace Modules\Shared\Application\Resources\Common;

class SelectOptionResource
{
    public function __construct(
        public string $value,
        public string $label
    ) {}

    public function toArray(): array
    {
        return [
            'value' => $this->value,
            'label' => $this->label,
        ];
    }

    public static function fromModel($model, string $valueField, string $labelField): self
    {
        return new self(
            (string) $model->$valueField,
            (string) $model->$labelField
        );
    }

    public static function collection(array $items): array
    {
        return array_map(fn($item) => $item->toArray(), $items);
    }
}
