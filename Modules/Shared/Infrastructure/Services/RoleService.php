<?php

namespace Modules\Shared\Infrastructure\Services;

use Modules\Shared\Application\Services\IRoleService;
use Modules\Shared\Application\Repositories\IRolePermissionRepository;
use Modules\Shared\Application\Requests\Role\RoleFilterRequest;
use Modules\Shared\Application\Requests\Role\CreateRoleRequest;
use Modules\Shared\Application\Requests\Role\UpdateRoleRequest;
use Modules\Shared\Application\Resources\Role\RoleResource;
use Modules\Shared\Domain\Entities\Role;
use Modules\Shared\Infrastructure\Helpers\UserLogHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

class RoleService implements IRoleService
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

    public function getRoles(RoleFilterRequest $request): array
    {
        return $this->repo->getFilteredRoles($request);
    }

    public function getRole(string $id): ?array
    {
        $role = $this->repo->getRoleById($id);
        if (!$role) return null;

        $permissions = $this->repo->getPermissionsByRoleId($role->id);

        $resource = new RoleResource($role, $permissions);
        return $resource->toArray();
    }

    public function getRoleForEdit(string $id): ?array
    {
        return $this->getRole($id);
    }

    public function createRole(CreateRoleRequest $request, ?string $createdBy): array
    {
        // Get role names from request (multiple separated by "=")
        $roleNames = $request->getRoleNames();

        if (empty($roleNames)) {
            return ['success' => false, 'message' => 'At least one valid role name is required'];
        }

        // Check for duplicates in request
        if (count($roleNames) !== count(array_unique($roleNames))) {
            return ['success' => false, 'message' => 'Duplicate role names found in request'];
        }

        // Check for existing roles
        $existingRoles = [];
        foreach ($roleNames as $roleName) {
            if ($this->repo->roleExists($roleName)) {
                $existingRoles[] = $roleName;
            }
        }

        if (!empty($existingRoles)) {
            return ['success' => false, 'message' => 'Role(s) already exist: ' . implode(', ', $existingRoles)];
        }

        $isActive = $request->getIsActiveBool();
        $createdByGuid = !empty($createdBy) && Str::isUuid($createdBy) ? $createdBy : null;

        DB::beginTransaction();

        try {
            $createdRoles = [];

            foreach ($roleNames as $roleName) {
                $role = new Role(
                    id: (string) Str::uuid(),
                    name: $roleName,
                    guardName: $request->guardName,
                    isActive: $isActive,
                    isDeleted: false,
                    deletedAt: null,
                    createdAt: Carbon::now()->toDateTimeImmutable(),
                    updatedAt: Carbon::now()->toDateTimeImmutable(),
                    createdBy: $createdByGuid,
                    updatedBy: $createdByGuid
                );

                $this->repo->createRole($role);
                $createdRoles[] = $role;

                // Assign permissions if provided
                if (!empty($request->permissions)) {
                    $this->repo->assignPermissionsToRole($role->id, $request->permissions);
                }
            }

            // Log the action
            $afterSnapshot = [
                'Roles' => array_map(function($r) {
                    return [
                        'id' => $r->id,
                        'name' => $r->name,
                        'guardName' => $r->guardName,
                        'isActive' => $r->isActive,
                        'createdBy' => $r->createdBy,
                        'createdAt' => $r->createdAt->format('Y-m-d H:i:s')
                    ];
                }, $createdRoles),
                'Permissions' => $request->permissions
            ];

            $changesJson = json_encode(['before' => null, 'after' => $afterSnapshot]);

            $this->userLogHelper->log(
                actionType: 'Create',
                detail: count($roleNames) . ' role(s) created: ' . implode(', ', $roleNames),
                changes: $changesJson,
                modelName: 'Role',
                modelId: $createdRoles[0]->id,
                userId: $createdByGuid ?? $createdRoles[0]->id
            );

            DB::commit();

            return ['success' => true, 'message' => count($roleNames) . ' role(s) created successfully'];
        } catch (\Exception $ex) {
            DB::rollBack();
            Log::error('Error creating roles: ' . $ex->getMessage(), [
                'trace' => $ex->getTraceAsString()
            ]);
            return ['success' => false, 'message' => 'Error creating roles: ' . $ex->getMessage()];
        }
    }

    public function updateRole(string $id, UpdateRoleRequest $request, ?string $updatedBy): array
    {
        $role = $this->repo->getRoleById($id);
        if (!$role) {
            return ['success' => false, 'message' => 'Role not found'];
        }

        // Check uniqueness
        if ($role->name !== $request->name && $this->repo->roleExists($request->name, $id)) {
            return ['success' => false, 'message' => 'Role name already exists'];
        }

        // Get current state for logging
        $beforeSnapshot = [
            'id' => $role->id,
            'name' => $role->name,
            'guardName' => $role->guardName,
            'isActive' => $role->isActive,
            'isDeleted' => $role->isDeleted,
            'Permissions' => $this->repo->getPermissionsByRoleId($role->id),
            'updatedBy' => $role->updatedBy,
            'updatedAt' => $role->updatedAt->format('Y-m-d H:i:s')
        ];

        $updatedByGuid = !empty($updatedBy) && Str::isUuid($updatedBy) ? $updatedBy : null;
        $isActive = $request->getIsActiveBool();

        DB::beginTransaction();

        try {
            // Update role
            $role->name = $request->name;
            $role->guardName = $request->guardName;
            $role->isActive = $isActive;
            $role->updatedAt = Carbon::now()->toDateTimeImmutable();
            $role->updatedBy = $updatedByGuid;

            $this->repo->updateRole($role);

            // Update permissions
            $this->repo->assignPermissionsToRole($role->id, $request->permissions ?? []);


            // Log the action
            $afterSnapshot = [
                'id' => $role->id,
                'name' => $role->name,
                'guardName' => $role->guardName,
                'isActive' => $role->isActive,
                'isDeleted' => $role->isDeleted,
                'Permissions' => $request->permissions,
                'updatedBy' => $updatedByGuid,
                'updatedAt' => $role->updatedAt->format('Y-m-d H:i:s')
            ];

            $changesJson = json_encode(['before' => $beforeSnapshot, 'after' => $afterSnapshot]);

            $this->userLogHelper->log(
                actionType: 'Update',
                detail: "Role '{$role->name}' was updated",
                changes: $changesJson,
                modelName: 'Role',
                modelId: $role->id,
                userId: $updatedByGuid ?? $role->id
            );

            DB::commit();

            return ['success' => true, 'message' => 'Role updated successfully'];
        } catch (\Exception $ex) {
            DB::rollBack();
            Log::error('Error updating role: ' . $ex->getMessage(), [
                'role_id' => $id,
                'trace' => $ex->getTraceAsString()
            ]);
            return ['success' => false, 'message' => 'Error updating role: ' . $ex->getMessage()];
        }
    }

    public function deleteRole(string $id, bool $permanent, ?string $currentUserId): array
    {
        $role = $this->repo->getRoleById($id);
        if (!$role) {
            return ['success' => false, 'message' => 'Role not found', 'deleteType' => 'none'];
        }

        if ($role->isDeleted) {
            return ['success' => false, 'message' => 'Role is already deleted', 'deleteType' => 'none'];
        }

        // Determine delete type
        $deleteType = 'soft';
        if ($permanent) {
            $hasRelatedRecords = $this->repo->roleHasRelatedRecords($id);
            if (!$hasRelatedRecords) {
                $deleteType = 'permanent';
            }
        }

        $deletedBy = !empty($currentUserId) && Str::isUuid($currentUserId) ? $currentUserId : null;

        DB::beginTransaction();

        try {
            $this->repo->deleteRole($id, $deleteType === 'permanent', $deletedBy);

            // Log the action
            $this->userLogHelper->log(
                actionType: 'Delete',
                detail: "Role '{$role->name}' was " . ($deleteType === 'permanent' ? 'permanently' : 'soft') . " deleted",
                changes: json_encode([
                    'before' => [
                        'id' => $role->id,
                        'name' => $role->name,
                        'guardName' => $role->guardName,
                        'isActive' => $role->isActive,
                        'isDeleted' => $role->isDeleted,
                        'deletedAt' => $role->deletedAt?->format('Y-m-d H:i:s')
                    ],
                    'after' => [
                        'isDeleted' => true,
                        'deletedAt' => Carbon::now()->toISOString(),
                        'deletedBy' => $deletedBy
                    ]
                ]),
                modelName: 'Role',
                modelId: $role->id,
                userId: $deletedBy ?? $role->id
            );

            DB::commit();

            return [
                'success' => true,
                'message' => "Role " . ($deleteType === 'permanent' ? 'permanently' : 'soft') . " deleted successfully",
                'deleteType' => $deleteType
            ];
        } catch (\Exception $ex) {
            DB::rollBack();
            Log::error('Error deleting role: ' . $ex->getMessage(), [
                'role_id' => $id,
                'permanent' => $permanent,
                'trace' => $ex->getTraceAsString()
            ]);
            return ['success' => false, 'message' => 'Error deleting role: ' . $ex->getMessage(), 'deleteType' => 'none'];
        }
    }

    public function restoreRole(string $id, ?string $currentUserId): array
    {
        $role = $this->repo->getRoleById($id);
        if (!$role) {
            return ['success' => false, 'message' => 'Role not found'];
        }

        if (!$role->isDeleted) {
            return ['success' => false, 'message' => 'Role is not deleted'];
        }

        $restoredBy = !empty($currentUserId) && Str::isUuid($currentUserId) ? $currentUserId : null;

        DB::beginTransaction();

        try {
            $this->repo->restoreRole($id);

            // Update the role with restoredBy info
            $role->isDeleted = false;
            $role->deletedAt = null;
            $role->updatedBy = $restoredBy;
            $role->updatedAt = Carbon::now()->toDateTimeImmutable();
            $this->repo->updateRole($role);
            // Log the action
            $this->userLogHelper->log(
                actionType: 'Restore',
                detail: "Role '{$role->name}' was restored",
                changes: json_encode([
                    'before' => [
                        'isDeleted' => true,
                        'deletedAt' => $role->deletedAt?->format('Y-m-d H:i:s')
                    ],
                    'after' => [
                        'isDeleted' => false,
                        'deletedAt' => null,
                        'restoredBy' => $restoredBy,
                        'restoredAt' => Carbon::now()->toISOString()
                    ]
                ]),
                modelName: 'Role',
                modelId: $role->id,
                userId: $restoredBy ?? $role->id
            );

            DB::commit();

            return ['success' => true, 'message' => 'Role restored successfully'];
        } catch (\Exception $ex) {
            DB::rollBack();
            Log::error('Error restoring role: ' . $ex->getMessage(), [
                'role_id' => $id,
                'trace' => $ex->getTraceAsString()
            ]);
            return ['success' => false, 'message' => 'Error restoring role: ' . $ex->getMessage()];
        }
    }

    public function checkDeleteEligibility(string $id): array
    {
        $role = $this->repo->getRoleById($id);
        if (!$role) {
            return ['success' => false, 'message' => 'Role not found', 'canBePermanent' => false];
        }

        if ($role->isDeleted) {
            return ['success' => false, 'message' => 'Role is already deleted', 'canBePermanent' => false];
        }

        $hasRelatedRecords = $this->repo->roleHasRelatedRecords($id);
        $canBePermanent = !$hasRelatedRecords;
        $message = $canBePermanent
            ? 'Role can be permanently deleted'
            : 'Role must be soft deleted due to existing related records';

        return ['success' => true, 'message' => $message, 'canBePermanent' => $canBePermanent];
    }
}
