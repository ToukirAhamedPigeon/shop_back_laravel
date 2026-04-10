<?php

namespace Modules\Shared\Application\Resources\Option;

use Modules\Shared\Domain\Entities\Option;
use Carbon\Carbon;

class OptionResource
{
    public string $id;
    public string $name;
    public ?string $parentId;
    public ?string $parentName;
    public bool $hasChild;
    public bool $isActive;
    public bool $isDeleted;
    public string $createdAt;
    public string $updatedAt;
    public ?string $createdByName;
    public ?string $updatedByName;
    public ?string $deletedByName;

    public function __construct(Option $option, ?string $parentName = null, ?string $createdByName = null, ?string $updatedByName = null, ?string $deletedByName = null)
    {
        $this->id = $option->id;
        $this->name = $option->name;
        $this->parentId = $option->parentId;
        $this->parentName = $parentName;
        $this->hasChild = $option->hasChild;
        $this->isActive = $option->isActive;
        $this->isDeleted = $option->isDeleted;
        $this->createdAt = Carbon::instance($option->createdAt)->toISOString();
        $this->updatedAt = Carbon::instance($option->updatedAt)->toISOString();
        $this->createdByName = $createdByName;
        $this->updatedByName = $updatedByName;
        $this->deletedByName = $deletedByName;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'parentId' => $this->parentId,
            'parentName' => $this->parentName,
            'hasChild' => $this->hasChild,
            'isActive' => $this->isActive,
            'isDeleted' => $this->isDeleted,
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt,
            'createdByName' => $this->createdByName,
            'updatedByName' => $this->updatedByName,
            'deletedByName' => $this->deletedByName,
        ];
    }

    public static function collection(array $options, array $parentNames = [], array $userNames = []): array
    {
        return array_map(function($option) use ($parentNames, $userNames) {
            // Get parent name using the option's ID as key
            $parentName = $parentNames[$option->id] ?? null;

            return (new self(
                $option,
                $parentName,
                $userNames[$option->createdBy] ?? null,
                $userNames[$option->updatedBy] ?? null,
                $userNames[$option->deletedBy] ?? null
            ))->toArray();
        }, $options);
    }
}
