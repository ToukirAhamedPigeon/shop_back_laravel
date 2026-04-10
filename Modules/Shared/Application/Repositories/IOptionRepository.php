<?php

namespace Modules\Shared\Application\Repositories;

use Modules\Shared\Domain\Entities\Option;
use Modules\Shared\Application\Requests\Option\OptionFilterRequest;

interface IOptionRepository
{
    public function getFilteredOptions(OptionFilterRequest $request): array;
    public function getOptionById(string $id): ?Option;
    public function optionExists(string $name, ?string $parentId, ?string $ignoreId = null): bool;
    public function createOption(Option $option): Option;
    public function updateOption(Option $option): Option;
    public function deleteOption(string $id, bool $permanent = false, ?string $deletedBy = null): void;
    public function restoreOption(string $id): void;
    public function optionHasChildren(string $optionId): bool;
    public function getChildrenCount(string $optionId): int;
    public function getParentOptions(bool $onlyWithChildren = true): array;
    public function saveChanges(): void;
}
