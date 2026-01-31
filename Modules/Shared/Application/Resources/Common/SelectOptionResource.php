<?php

namespace Modules\Shared\Application\Resources\Common;

use Illuminate\Http\Resources\Json\JsonResource;

class SelectOptionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'value' => (string) $this->value,
            'label' => (string) $this->label,
        ];
    }
}
