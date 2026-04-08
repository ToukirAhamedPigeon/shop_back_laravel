<?php

namespace Modules\Shared\Application\Resources\Translation;

use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class TranslationResource extends JsonResource
{
    protected ?string $createdByName;
    protected ?string $updatedByName;

    public function __construct($resource, ?string $createdByName = null, ?string $updatedByName = null)
    {
        parent::__construct($resource);
        $this->createdByName = $createdByName;
        $this->updatedByName = $updatedByName;
    }

    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'key' => $this->key,
            'module' => $this->module,
            'englishValue' => $this->englishValue ?? $this->values->firstWhere('lang', 'en')?->value ?? '',
            'banglaValue' => $this->banglaValue ?? $this->values->firstWhere('lang', 'bn')?->value ?? '',
            'createdAt' => $this->created_at ? Carbon::parse($this->created_at)->toISOString() : null,
            'updatedAt' => $this->updated_at ? Carbon::parse($this->updated_at)->toISOString() : null,
            'createdBy' => $this->created_by,
            'updatedBy' => $this->updated_by,
            'createdByName' => $this->createdByName,
            'updatedByName' => $this->updatedByName,
        ];
    }

    public static function collection($resources, array $usersMap = []): array
    {
        return $resources->map(function ($resource) use ($usersMap) {
            // Add english and bangla values if not already set
            if (!isset($resource->englishValue)) {
                $resource->englishValue = $resource->values->firstWhere('lang', 'en')?->value ?? '';
            }
            if (!isset($resource->banglaValue)) {
                $resource->banglaValue = $resource->values->firstWhere('lang', 'bn')?->value ?? '';
            }

            $createdByName = $resource->created_by && isset($usersMap[$resource->created_by])
                ? $usersMap[$resource->created_by]
                : null;
            $updatedByName = $resource->updated_by && isset($usersMap[$resource->updated_by])
                ? $usersMap[$resource->updated_by]
                : null;

            return (new self($resource, $createdByName, $updatedByName))->toArray(request());
        })->toArray();
    }
}
