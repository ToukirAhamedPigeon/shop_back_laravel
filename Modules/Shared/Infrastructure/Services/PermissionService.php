<?php

namespace Modules\Shared\Infrastructure\Services;

use Modules\Shared\Application\Services\IPermissionService;
use Modules\Shared\Application\Repositories\IRolePermissionRepository;
use Modules\Shared\Application\Requests\Permission\PermissionFilterRequest;
use Modules\Shared\Application\Requests\Permission\CreatePermissionRequest;
use Modules\Shared\Application\Requests\Permission\UpdatePermissionRequest;
use Modules\Shared\Application\Resources\Permission\PermissionResource;
use Modules\Shared\Application\Resources\Common\BulkOperationResource;
use Modules\Shared\Domain\Entities\Permission;
use Modules\Shared\Infrastructure\Helpers\UserLogHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

class PermissionService implements IPermissionService
{
    private IRolePermissionRepository $repo;
    private UserLogHelper $userLogHelper;

    public function __construct(
        IRolePermissionRepository $repo,
        UserLogHelper $userLogHelper
    ) {
        $this->repo = $repo;
        $this->userLogHelper = $userLogHelper;
    }

    public function getPermissions(PermissionFilterRequest $request): array
    {
        return $this->repo->getFilteredPermissions($request);
    }

    public function getPermission(string $id): ?array
    {
        $permission = $this->repo->getPermissionById($id);
        if (!$permission) return null;

        $roles = $this->repo->getRolesByPermissionId($permission->id);

        $resource = new PermissionResource($permission, $roles);
        return $resource->toArray();
    }

    public function getPermissionForEdit(string $id): ?array
    {
        return $this->getPermission($id);
    }

    public function createPermission(CreatePermissionRequest $request, ?string $createdBy): array
    {
        // Get permission names from request (multiple separated by "=")
        $permissionNames = $request->getPermissionNames();

        if (empty($permissionNames)) {
            return ['success' => false, 'message' => 'At least one valid permission name is required'];
        }

        // Check for duplicates in request
        if (count($permissionNames) !== count(array_unique($permissionNames))) {
            return ['success' => false, 'message' => 'Duplicate permission names found in request'];
        }

        // Check for existing permissions
        $existingPermissions = [];
        foreach ($permissionNames as $permissionName) {
            if ($this->repo->permissionExists($permissionName)) {
                $existingPermissions[] = $permissionName;
            }
        }

        if (!empty($existingPermissions)) {
            return ['success' => false, 'message' => 'Permission(s) already exist: ' . implode(', ', $existingPermissions)];
        }

        $isActive = $request->getIsActiveBool();
        $createdByGuid = !empty($createdBy) && Str::isUuid($createdBy) ? $createdBy : null;

        DB::beginTransaction();

        try {
            $createdPermissions = [];

            foreach ($permissionNames as $permissionName) {
                $permission = new Permission(
                    id: (string) Str::uuid(),
                    name: $permissionName,
                    guardName: $request->guardName,
                    isActive: $isActive,
                    isDeleted: false,
                    deletedAt: null,
                    createdAt: Carbon::now()->toDateTimeImmutable(),
                    updatedAt: Carbon::now()->toDateTimeImmutable(),
                    createdBy: $createdByGuid,
                    updatedBy: $createdByGuid
                );

                $this->repo->createPermission($permission);
                $createdPermissions[] = $permission;

                // Assign roles if provided
                if (!empty($request->roles)) {
                    $this->repo->assignRolesToPermission($permission->id, $request->roles);
                }
            }

            // Log the action
            $afterSnapshot = [
                'Permissions' => array_map(function($p) {
                    return [
                        'id' => $p->id,
                        'name' => $p->name,
                        'guardName' => $p->guardName,
                        'isActive' => $p->isActive,
                        'createdBy' => $p->createdBy,
                        'createdAt' => $p->createdAt->format('Y-m-d H:i:s')
                    ];
                }, $createdPermissions),
                'Roles' => $request->roles
            ];

            $changesJson = json_encode(['before' => null, 'after' => $afterSnapshot]);

            $this->userLogHelper->log(
                actionType: 'Create',
                detail: count($permissionNames) . ' permission(s) created: ' . implode(', ', $permissionNames),
                changes: $changesJson,
                modelName: 'Permission',
                modelId: $createdPermissions[0]->id,
                userId: $createdByGuid ?? $createdPermissions[0]->id
            );

            DB::commit();

            return ['success' => true, 'message' => count($permissionNames) . ' permission(s) created successfully'];
        } catch (\Exception $ex) {
            DB::rollBack();
            Log::error('Error creating permissions: ' . $ex->getMessage(), [
                'trace' => $ex->getTraceAsString()
            ]);
            return ['success' => false, 'message' => 'Error creating permissions: ' . $ex->getMessage()];
        }
    }

    public function updatePermission(string $id, UpdatePermissionRequest $request, ?string $updatedBy): array
    {
        $permission = $this->repo->getPermissionById($id);
        if (!$permission) {
            return ['success' => false, 'message' => 'Permission not found'];
        }

        // Check uniqueness
        if ($permission->name !== $request->name && $this->repo->permissionExists($request->name, $id)) {
            return ['success' => false, 'message' => 'Permission name already exists'];
        }

        // Get current state for logging
        $beforeSnapshot = [
            'id' => $permission->id,
            'name' => $permission->name,
            'guardName' => $permission->guardName,
            'isActive' => $permission->isActive,
            'isDeleted' => $permission->isDeleted,
            'Roles' => $this->repo->getRolesByPermissionId($permission->id),
            'updatedBy' => $permission->updatedBy,
            'updatedAt' => $permission->updatedAt->format('Y-m-d H:i:s')
        ];

        $updatedByGuid = !empty($updatedBy) && Str::isUuid($updatedBy) ? $updatedBy : null;
        $isActive = $request->getIsActiveBool();

        DB::beginTransaction();

        try {
            // Update permission
            $permission->name = $request->name;
            $permission->guardName = $request->guardName;
            $permission->isActive = $isActive;
            $permission->updatedAt = Carbon::now()->toDateTimeImmutable();
            $permission->updatedBy = $updatedByGuid;

            $this->repo->updatePermission($permission);

            // Update roles
            $this->repo->assignRolesToPermission($permission->id, $request->roles ?? []);

            // Log the action
            $afterSnapshot = [
                'id' => $permission->id,
                'name' => $permission->name,
                'guardName' => $permission->guardName,
                'isActive' => $permission->isActive,
                'isDeleted' => $permission->isDeleted,
                'Roles' => $request->roles,
                'updatedBy' => $updatedByGuid,
                'updatedAt' => $permission->updatedAt->format('Y-m-d H:i:s')
            ];

            $changesJson = json_encode(['before' => $beforeSnapshot, 'after' => $afterSnapshot]);

            $this->userLogHelper->log(
                actionType: 'Update',
                detail: "Permission '{$permission->name}' was updated",
                changes: $changesJson,
                modelName: 'Permission',
                modelId: $permission->id,
                userId: $updatedByGuid ?? $permission->id
            );

            DB::commit();

            return ['success' => true, 'message' => 'Permission updated successfully'];
        } catch (\Exception $ex) {
            DB::rollBack();
            Log::error('Error updating permission: ' . $ex->getMessage(), [
                'permission_id' => $id,
                'trace' => $ex->getTraceAsString()
            ]);
            return ['success' => false, 'message' => 'Error updating permission: ' . $ex->getMessage()];
        }
    }

    public function deletePermission(string $id, bool $permanent, ?string $currentUserId): array
    {
        $permission = $this->repo->getPermissionById($id);
        if (!$permission) {
            return ['success' => false, 'message' => 'Permission not found', 'deleteType' => 'none'];
        }

        if ($permission->isDeleted) {
            return ['success' => false, 'message' => 'Permission is already deleted', 'deleteType' => 'none'];
        }

        // Determine delete type
        $deleteType = 'soft';
        if ($permanent) {
            $hasRelatedRecords = $this->repo->permissionHasRelatedRecords($id);
            if (!$hasRelatedRecords) {
                $deleteType = 'permanent';
            }
        }

        $deletedBy = !empty($currentUserId) && Str::isUuid($currentUserId) ? $currentUserId : null;

        DB::beginTransaction();

        try {
            $this->repo->deletePermission($id, $deleteType === 'permanent', $deletedBy);

            // Log the action
            $this->userLogHelper->log(
                actionType: 'Delete',
                detail: "Permission '{$permission->name}' was " . ($deleteType === 'permanent' ? 'permanently' : 'soft') . " deleted",
                changes: json_encode([
                    'before' => [
                        'id' => $permission->id,
                        'name' => $permission->name,
                        'guardName' => $permission->guardName,
                        'isActive' => $permission->isActive,
                        'isDeleted' => $permission->isDeleted,
                        'deletedAt' => $permission->deletedAt?->format('Y-m-d H:i:s')
                    ],
                    'after' => [
                        'isDeleted' => true,
                        'deletedAt' => Carbon::now()->toISOString(),
                        'deletedBy' => $deletedBy
                    ]
                ]),
                modelName: 'Permission',
                modelId: $permission->id,
                userId: $deletedBy ?? $permission->id
            );

            DB::commit();

            return [
                'success' => true,
                'message' => "Permission " . ($deleteType === 'permanent' ? 'permanently' : 'soft') . " deleted successfully",
                'deleteType' => $deleteType
            ];
        } catch (\Exception $ex) {
            DB::rollBack();
            Log::error('Error deleting permission: ' . $ex->getMessage(), [
                'permission_id' => $id,
                'permanent' => $permanent,
                'trace' => $ex->getTraceAsString()
            ]);
            return ['success' => false, 'message' => 'Error deleting permission: ' . $ex->getMessage(), 'deleteType' => 'none'];
        }
    }

    public function restorePermission(string $id, ?string $currentUserId): array
    {
        $permission = $this->repo->getPermissionById($id);
        if (!$permission) {
            return ['success' => false, 'message' => 'Permission not found'];
        }

        if (!$permission->isDeleted) {
            return ['success' => false, 'message' => 'Permission is not deleted'];
        }

        $restoredBy = !empty($currentUserId) && Str::isUuid($currentUserId) ? $currentUserId : null;

        DB::beginTransaction();

        try {
            $this->repo->restorePermission($id);

            // Update the permission with restoredBy info
            $permission->isDeleted = false;
            $permission->deletedAt = null;
            $permission->updatedBy = $restoredBy;
            $permission->updatedAt = Carbon::now()->toDateTimeImmutable();
            $this->repo->updatePermission($permission);


            // Log the action
            $this->userLogHelper->log(
                actionType: 'Restore',
                detail: "Permission '{$permission->name}' was restored",
                changes: json_encode([
                    'before' => [
                        'isDeleted' => true,
                        'deletedAt' => $permission->deletedAt?->format('Y-m-d H:i:s')
                    ],
                    'after' => [
                        'isDeleted' => false,
                        'deletedAt' => null,
                        'restoredBy' => $restoredBy,
                        'restoredAt' => Carbon::now()->toISOString()
                    ]
                ]),
                modelName: 'Permission',
                modelId: $permission->id,
                userId: $restoredBy ?? $permission->id
            );

            DB::commit();

            return ['success' => true, 'message' => 'Permission restored successfully'];
        } catch (\Exception $ex) {
            DB::rollBack();
            Log::error('Error restoring permission: ' . $ex->getMessage(), [
                'permission_id' => $id,
                'trace' => $ex->getTraceAsString()
            ]);
            return ['success' => false, 'message' => 'Error restoring permission: ' . $ex->getMessage()];
        }
    }

    public function checkDeleteEligibility(string $id): array
    {
        $permission = $this->repo->getPermissionById($id);
        if (!$permission) {
            return ['success' => false, 'message' => 'Permission not found', 'canBePermanent' => false];
        }

        if ($permission->isDeleted) {
            return ['success' => false, 'message' => 'Permission is already deleted', 'canBePermanent' => false];
        }

        $hasRelatedRecords = $this->repo->permissionHasRelatedRecords($id);
        $canBePermanent = !$hasRelatedRecords;
        $message = $canBePermanent
            ? 'Permission can be permanently deleted'
            : 'Permission must be soft deleted due to existing related records';

        return ['success' => true, 'message' => $message, 'canBePermanent' => $canBePermanent];
    }
     // ==================== BULK OPERATIONS ====================

    public function bulkDeletePermissions(array $ids, bool $permanent, ?string $currentUserId): BulkOperationResource
    {
        $deletedBy = null;
        if (!empty($currentUserId) && \Illuminate\Support\Str::isUuid($currentUserId)) {
            $deletedBy = $currentUserId;
        }

        $result = $this->repo->bulkDeletePermissions($ids, $permanent, $deletedBy);

        // Log the bulk operation
        if ($result['successCount'] > 0) {
            $this->userLogHelper->log(
                actionType: 'BulkDelete',
                detail: "Bulk " . ($permanent ? "permanent" : "soft") . " delete of {$result['successCount']} permission(s). Failed: {$result['failedCount']}",
                changes: json_encode([
                    'ids' => $ids,
                    'permanent' => $permanent,
                    'successCount' => $result['successCount'],
                    'failedCount' => $result['failedCount'],
                    'errors' => $result['errors']
                ]),
                modelName: 'Permission',
                modelId: 'bulk',
                userId: $deletedBy ?? ''
            );
        }

        return new BulkOperationResource($result);
    }

    public function bulkRestorePermissions(array $ids, ?string $currentUserId): BulkOperationResource
    {
        $restoredBy = null;
        if (!empty($currentUserId) && \Illuminate\Support\Str::isUuid($currentUserId)) {
            $restoredBy = $currentUserId;
        }

        $result = $this->repo->bulkRestorePermissions($ids, $restoredBy);

        // Log the bulk operation
        if ($result['successCount'] > 0) {
            $this->userLogHelper->log(
                actionType: 'BulkRestore',
                detail: "Bulk restore of {$result['successCount']} permission(s). Failed: {$result['failedCount']}",
                changes: json_encode([
                    'ids' => $ids,
                    'successCount' => $result['successCount'],
                    'failedCount' => $result['failedCount'],
                    'errors' => $result['errors']
                ]),
                modelName: 'Permission',
                modelId: 'bulk',
                userId: $restoredBy ?? ''
            );
        }

        return new BulkOperationResource($result);
    }
}
