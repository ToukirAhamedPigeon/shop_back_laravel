<?php

namespace Modules\Shared\Application\Services;

use Modules\Shared\Application\Requests\Common\SelectOptionRequest;
use Modules\Shared\Application\Requests\Option\OptionFilterRequest;
use Modules\Shared\Application\Requests\Option\CreateOptionRequest;
use Modules\Shared\Application\Requests\Option\UpdateOptionRequest;

interface IOptionsService
{
    // Existing method for select options
    public function getOptions(string $type, SelectOptionRequest $req): array;

    // CRUD Operations for Options
    public function getOptionsPaginated(OptionFilterRequest $request): array;
    public function getOption(string $id): ?array;
    public function getOptionForEdit(string $id): ?array;
    public function createOption(CreateOptionRequest $request, ?string $createdBy): array;
    public function updateOption(string $id, UpdateOptionRequest $request, ?string $updatedBy): array;
    public function deleteOption(string $id, bool $permanent, ?string $currentUserId): array;
    public function restoreOption(string $id, ?string $currentUserId): array;
    public function checkDeleteEligibility(string $id): array;
    public function getParentOptions(SelectOptionRequest $request): array;

    // Bulk operations - return array for Resource
    public function bulkDeleteOptions(array $ids, bool $permanent, ?string $currentUserId): array;
    public function bulkRestoreOptions(array $ids, ?string $currentUserId): array;
}
