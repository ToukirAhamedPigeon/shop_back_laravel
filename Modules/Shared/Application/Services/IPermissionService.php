<?php

namespace Modules\Shared\Application\Services;

use Modules\Shared\Application\Requests\Permission\PermissionFilterRequest;
use Modules\Shared\Application\Requests\Permission\CreatePermissionRequest;
use Modules\Shared\Application\Requests\Permission\UpdatePermissionRequest;
use Modules\Shared\Application\Resources\Common\BulkOperationResource;

interface IPermissionService
{
    public function getPermissions(PermissionFilterRequest $request): array;
    public function getPermission(string $id): ?array;
    public function getPermissionForEdit(string $id): ?array;
    public function createPermission(CreatePermissionRequest $request, ?string $createdBy): array;
    public function updatePermission(string $id, UpdatePermissionRequest $request, ?string $updatedBy): array;
    public function deletePermission(string $id, bool $permanent, ?string $currentUserId): array;
    public function restorePermission(string $id, ?string $currentUserId): array;
    public function checkDeleteEligibility(string $id): array;
    // ==================== BULK OPERATIONS ====================

    /**
     * Bulk delete permissions (soft or permanent)
     */
    public function bulkDeletePermissions(array $ids, bool $permanent, ?string $currentUserId): BulkOperationResource;

    /**
     * Bulk restore permissions
     */
    public function bulkRestorePermissions(array $ids, ?string $currentUserId): BulkOperationResource;
}
