<?php

namespace Modules\Shared\Application\Repositories;

use Modules\Shared\Domain\Entities\Option;
use Modules\Shared\Application\Requests\Option\OptionFilterRequest;

interface IOptionRepository
{
    public function getFilteredOptions(OptionFilterRequest $request): array;
    public function getOptionById(string $id): ?Option;
    public function getOptionByIdIncludingDeleted(string $id): ?Option;
    public function optionExists(string $name, ?string $parentId, ?string $ignoreId = null): bool;
    public function createOption(Option $option): Option;
    public function updateOption(Option $option): Option;
    public function deleteOption(string $id, bool $permanent = false, ?string $deletedBy = null): void;
    public function restoreOption(string $id, ?string $restoredBy = null): void;
    public function optionHasChildren(string $optionId): bool;
    public function getChildrenCount(string $optionId): int;
    public function getParentOptions(bool $onlyWithChildren = true): array;
    public function saveChanges(): void;

    // Bulk operations - return array instead of resource
    public function bulkDeleteOptions(array $ids, bool $permanent, ?string $deletedBy): array;
    public function bulkRestoreOptions(array $ids, ?string $restoredBy): array;
}
