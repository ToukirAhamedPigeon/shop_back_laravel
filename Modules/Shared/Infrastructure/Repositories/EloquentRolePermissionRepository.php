<?php

namespace Modules\Shared\Infrastructure\Repositories;

use Modules\Shared\Application\Repositories\IRolePermissionRepository;
use Modules\Shared\Domain\Entities\Role as RoleEntity;
use Modules\Shared\Domain\Entities\Permission as PermissionEntity;
use Modules\Shared\Domain\Entities\RolePermission as RolePermissionEntity;
use Modules\Shared\Domain\Entities\ModelPermission as ModelPermissionEntity;
use Modules\Shared\Domain\Entities\ModelRole as ModelRoleEntity;
use Modules\Shared\Infrastructure\Models\EloquentRole;
use Modules\Shared\Infrastructure\Models\EloquentPermission;
use Modules\Shared\Infrastructure\Models\EloquentRolePermission;
use Modules\Shared\Infrastructure\Models\EloquentModelPermission;
use Modules\Shared\Infrastructure\Models\EloquentModelRole;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class EloquentRolePermissionRepository implements IRolePermissionRepository
{
    // ==================== ROLE METHODS ====================

    public function findRoleById(string $id): ?RoleEntity
    {
        $model = EloquentRole::find($id);
        return $model ? $this->mapRoleToEntity($model) : null;
    }

    public function findRoleByName(string $name): ?RoleEntity
    {
        $model = EloquentRole::where('name', $name)->first();
        return $model ? $this->mapRoleToEntity($model) : null;
    }

    public function findAllRoles(array $filters = []): array
    {
        $query = EloquentRole::query();

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (isset($filters['is_deleted'])) {
            $query->where('is_deleted', $filters['is_deleted']);
        }

        return $query->orderBy('name')
            ->get()
            ->map(fn ($m) => $this->mapRoleToEntity($m))
            ->toArray();
    }

    public function createRole(RoleEntity $role): RoleEntity
    {
        $model = new EloquentRole();
        $model->id = $role->id;
        $model->name = $role->name;
        $model->guard_name = $role->guardName;
        $model->is_active = $role->isActive;
        $model->is_deleted = $role->isDeleted;
        $model->created_at = $role->createdAt->format('Y-m-d H:i:s');
        $model->updated_at = $role->updatedAt->format('Y-m-d H:i:s');
        $model->save();

        return $this->mapRoleToEntity($model);
    }

    public function updateRole(RoleEntity $role): RoleEntity
    {
        $model = EloquentRole::findOrFail($role->id);
        $model->name = $role->name;
        $model->guard_name = $role->guardName;
        $model->is_active = $role->isActive;
        $model->is_deleted = $role->isDeleted;
        $model->updated_at = $role->updatedAt->format('Y-m-d H:i:s');
        $model->save();

        return $this->mapRoleToEntity($model);
    }

    public function deleteRole(string $id): void
    {
        EloquentRole::where('id', $id)->update(['is_deleted' => true]);
    }

    public function restoreRole(string $id): void
    {
        EloquentRole::where('id', $id)->update(['is_deleted' => false]);
    }

    // ==================== PERMISSION METHODS ====================

    public function findPermissionById(string $id): ?PermissionEntity
    {
        $model = EloquentPermission::find($id);
        return $model ? $this->mapPermissionToEntity($model) : null;
    }

    public function findPermissionByName(string $name): ?PermissionEntity
    {
        $model = EloquentPermission::where('name', $name)->first();
        return $model ? $this->mapPermissionToEntity($model) : null;
    }

    public function findAllPermissions(array $filters = []): array
    {
        $query = EloquentPermission::query();

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (isset($filters['is_deleted'])) {
            $query->where('is_deleted', $filters['is_deleted']);
        }

        return $query->orderBy('name')
            ->get()
            ->map(fn ($m) => $this->mapPermissionToEntity($m))
            ->toArray();
    }

    public function createPermission(PermissionEntity $permission): PermissionEntity
    {
        $model = new EloquentPermission();
        $model->id = $permission->id;
        $model->name = $permission->name;
        $model->guard_name = $permission->guardName;
        $model->is_active = $permission->isActive;
        $model->is_deleted = $permission->isDeleted;
        $model->created_at = $permission->createdAt->format('Y-m-d H:i:s');
        $model->updated_at = $permission->updatedAt->format('Y-m-d H:i:s');
        $model->save();

        return $this->mapPermissionToEntity($model);
    }

    public function updatePermission(PermissionEntity $permission): PermissionEntity
    {
        $model = EloquentPermission::findOrFail($permission->id);
        $model->name = $permission->name;
        $model->guard_name = $permission->guardName;
        $model->is_active = $permission->isActive;
        $model->is_deleted = $permission->isDeleted;
        $model->updated_at = $permission->updatedAt->format('Y-m-d H:i:s');
        $model->save();

        return $this->mapPermissionToEntity($model);
    }

    public function deletePermission(string $id): void
    {
        EloquentPermission::where('id', $id)->update(['is_deleted' => true]);
    }

    public function restorePermission(string $id): void
    {
        EloquentPermission::where('id', $id)->update(['is_deleted' => false]);
    }

    // ==================== ROLE-PERMISSION METHODS ====================

    public function findRolePermissionById(string $id): ?RolePermissionEntity
    {
        $model = EloquentRolePermission::find($id);
        return $model ? $this->mapRolePermissionToEntity($model) : null;
    }

    public function findRolePermissionByRoleAndPermission(string $roleId, string $permissionId): ?RolePermissionEntity
    {
        $model = EloquentRolePermission::where('role_id', $roleId)
            ->where('permission_id', $permissionId)
            ->first();

        return $model ? $this->mapRolePermissionToEntity($model) : null;
    }

    public function findAllRolePermissionsByRoleId(string $roleId): array
    {
        return EloquentRolePermission::where('role_id', $roleId)
            ->with('permission')
            ->get()
            ->map(fn ($m) => $this->mapRolePermissionToEntity($m))
            ->toArray();
    }

    public function findAllRolePermissionsByPermissionId(string $permissionId): array
    {
        return EloquentRolePermission::where('permission_id', $permissionId)
            ->with('role')
            ->get()
            ->map(fn ($m) => $this->mapRolePermissionToEntity($m))
            ->toArray();
    }

    public function createRolePermission(RolePermissionEntity $rolePermission): RolePermissionEntity
    {
        $model = new EloquentRolePermission();
        $model->id = $rolePermission->id;
        $model->role_id = $rolePermission->roleId;
        $model->permission_id = $rolePermission->permissionId;
        $model->created_at = $rolePermission->createdAt->format('Y-m-d H:i:s');
        $model->updated_at = $rolePermission->updatedAt->format('Y-m-d H:i:s');
        $model->save();

        return $this->mapRolePermissionToEntity($model);
    }

    public function deleteRolePermission(string $id): void
    {
        EloquentRolePermission::where('id', $id)->delete();
    }

    public function deleteRolePermissionsByRole(string $roleId): void
    {
        EloquentRolePermission::where('role_id', $roleId)->delete();
    }

    // ==================== MODEL-PERMISSION METHODS ====================

    public function findModelPermissionById(string $id): ?ModelPermissionEntity
    {
        $model = EloquentModelPermission::find($id);
        return $model ? $this->mapModelPermissionToEntity($model) : null;
    }

    public function findAllModelPermissionsByModel(string $modelId, string $modelName = 'User'): array
    {
        return EloquentModelPermission::where('model_id', $modelId)
            ->where('model_name', $modelName)
            ->with('permission')
            ->get()
            ->map(fn ($m) => $this->mapModelPermissionToEntity($m))
            ->toArray();
    }

    public function createModelPermission(ModelPermissionEntity $modelPermission): ModelPermissionEntity
    {
        $model = new EloquentModelPermission();
        $model->id = $modelPermission->id;
        $model->model_id = $modelPermission->modelId;
        $model->permission_id = $modelPermission->permissionId;
        $model->model_name = $modelPermission->modelName;
        $model->created_at = $modelPermission->createdAt->format('Y-m-d H:i:s');
        $model->updated_at = $modelPermission->updatedAt->format('Y-m-d H:i:s');
        $model->save();

        return $this->mapModelPermissionToEntity($model);
    }

    public function deleteModelPermission(string $id): void
    {
        EloquentModelPermission::where('id', $id)->delete();
    }

    public function deleteModelPermissionsByModel(string $modelId, string $modelName = 'User'): void
    {
        EloquentModelPermission::where('model_id', $modelId)
            ->where('model_name', $modelName)
            ->delete();
    }

    // ==================== MODEL-ROLE METHODS ====================

    public function findModelRoleById(string $id): ?ModelRoleEntity
    {
        $model = EloquentModelRole::find($id);
        return $model ? $this->mapModelRoleToEntity($model) : null;
    }

    public function findAllModelRolesByModel(string $modelId, string $modelName = 'User'): array
    {
        return EloquentModelRole::where('model_id', $modelId)
            ->where('model_name', $modelName)
            ->with('role')
            ->get()
            ->map(fn ($m) => $this->mapModelRoleToEntity($m))
            ->toArray();
    }

    public function createModelRole(ModelRoleEntity $modelRole): ModelRoleEntity
    {
        $model = new EloquentModelRole();
        $model->id = $modelRole->id;
        $model->model_id = $modelRole->modelId;
        $model->role_id = $modelRole->roleId;
        $model->model_name = $modelRole->modelName;
        $model->created_at = $modelRole->createdAt->format('Y-m-d H:i:s');
        $model->updated_at = $modelRole->updatedAt->format('Y-m-d H:i:s');
        $model->save();

        return $this->mapModelRoleToEntity($model);
    }

    public function deleteModelRole(string $id): void
    {
        EloquentModelRole::where('id', $id)->delete();
    }

    public function deleteModelRolesByModel(string $modelId, string $modelName = 'User'): void
    {
        EloquentModelRole::where('model_id', $modelId)
            ->where('model_name', $modelName)
            ->delete();
    }

    // ==================== USER PERMISSION HELPER METHODS ====================

    public function getPermissionsByRoleId(string $roleId): array
    {
        $role = EloquentRole::with(['rolePermissions.permission'])
            ->find($roleId);

        return $role
            ? $role->rolePermissions->pluck('permission.name')->toArray()
            : [];
    }

    public function getRolesByPermissionId(string $permissionId): array
    {
        $permission = EloquentPermission::with('rolePermissions.role')->find($permissionId);
        return $permission ? $permission->rolePermissions->pluck('role.name')->toArray() : [];
    }

    public function getRoleNamesByUserId(string $userId, string $modelName = "User"): array
    {
        return EloquentModelRole::where('model_id', $userId)
            ->where('model_name', $modelName)
            ->with('role')
            ->get()
            ->pluck('role.name')
            ->unique()
            ->toArray();
    }

    public function getRolePermissionsByUserId(string $userId): array
    {
        return EloquentModelRole::where('model_id', $userId)
            ->where('model_name', 'User')
            ->with('role.rolePermissions.permission')
            ->get()
            ->flatMap(fn($mr) => $mr->role->rolePermissions->pluck('permission.name'))
            ->unique()
            ->toArray();
    }

    public function getModelPermissionsByUserId(string $userId): array
    {
        return EloquentModelPermission::where('model_id', $userId)
            ->where('model_name', 'User')
            ->with('permission')
            ->get()
            ->pluck('permission.name')
            ->unique()
            ->toArray();
    }

    public function getAllPermissionsByUserId(string $userId): array
    {
        try {
            $permissions = [];

            // Get permissions from roles
            $rolePermissions = DB::table('model_roles')
                ->join('role_permissions', 'model_roles.role_id', '=', 'role_permissions.role_id')
                ->join('permissions', 'role_permissions.permission_id', '=', 'permissions.id')
                ->where('model_roles.model_id', $userId)
                ->where('model_roles.model_name', 'User')
                ->where('permissions.is_deleted', false)
                ->pluck('permissions.name')
                ->toArray();

            // Get direct permissions
            $directPermissions = DB::table('model_permissions')
                ->join('permissions', 'model_permissions.permission_id', '=', 'permissions.id')
                ->where('model_permissions.model_id', $userId)
                ->where('model_permissions.model_name', 'User')
                ->where('permissions.is_deleted', false)
                ->pluck('permissions.name')
                ->toArray();

            return array_unique(array_merge($rolePermissions, $directPermissions));

        } catch (\Exception $e) {
            Log::error('Error in getAllPermissionsByUserId: ' . $e->getMessage());
            return [];
        }
    }
    // Add to EloquentRolePermissionRepository

    public function getAllRoles(): array
    {
        return EloquentRole::where('is_deleted', false)
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name')
            ->toArray();
    }

    public function getAllPermissions(): array
    {
        return EloquentPermission::where('is_deleted', false)
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name')
            ->toArray();
    }

    // Async versions
    public function getAllRolesAsync(): array
    {
        return $this->getAllRoles();
    }

    public function getAllPermissionsAsync(): array
    {
        return $this->getAllPermissions();
    }

     // ==================== NEW METHODS FOR USER SERVICE ====================

    /**
     * Assign roles to a user
     */
    public function assignRolesToUser(string $userId, array $roles): void
    {
        foreach ($roles as $roleName) {
            $role = EloquentRole::where('name', $roleName)->first();
            if (!$role) continue;

            $exists = EloquentModelRole::where('model_id', $userId)
                ->where('model_name', 'User')
                ->where('role_id', $role->id)
                ->exists();

            if (!$exists) {
                EloquentModelRole::create([
                    'id' => (string) Str::uuid(),
                    'model_id' => $userId,
                    'role_id' => $role->id,
                    'model_name' => 'User',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Assign permissions directly to a user
     */
    public function assignPermissionsToUser(string $userId, array $permissions): void
    {
        foreach ($permissions as $permissionName) {
            $permission = EloquentPermission::where('name', $permissionName)->first();
            if (!$permission) continue;

            $exists = EloquentModelPermission::where('model_id', $userId)
                ->where('model_name', 'User')
                ->where('permission_id', $permission->id)
                ->exists();

            if (!$exists) {
                EloquentModelPermission::create([
                    'id' => (string) Str::uuid(),
                    'model_id' => $userId,
                    'permission_id' => $permission->id,
                    'model_name' => 'User',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Set roles for a user (replaces existing ones)
     */
    public function setRolesForUser(string $userId, array $roles): void
    {
        // Remove all existing roles
        $this->removeAllRolesFromUser($userId);

        // Assign new roles
        $this->assignRolesToUser($userId, $roles);
    }

    /**
     * Set permissions for a user (replaces existing direct permissions)
     */
    public function setPermissionsForUser(string $userId, array $permissions): void
    {
        // Remove all existing direct permissions
        $this->removeAllPermissionsFromUser($userId);

        // Assign new permissions
        $this->assignPermissionsToUser($userId, $permissions);
    }

    /**
     * Validate that all role names exist
     *
     * @return array Array of valid role names
     */
    public function validateRolesExist(array $roles): array
    {
        if (empty($roles)) {
            return [];
        }

        return EloquentRole::whereIn('name', $roles)
            ->where('is_deleted', false)
            ->where('is_active', true)
            ->pluck('name')
            ->toArray();
    }

    /**
     * Get all permission names for given role names
     */
    public function getPermissionsByRoleNames(array $roles): array
    {
        if (empty($roles)) {
            return [];
        }

        return EloquentRole::whereIn('name', $roles)
            ->with(['rolePermissions.permission'])
            ->get()
            ->flatMap(function ($role) {
                return $role->rolePermissions->pluck('permission.name');
            })
            ->unique()
            ->toArray();
    }

    /**
     * Remove all roles from a user
     */
    public function removeAllRolesFromUser(string $userId): void
    {
        EloquentModelRole::where('model_id', $userId)
            ->where('model_name', 'User')
            ->delete();
    }

    /**
     * Remove all direct permissions from a user
     */
    public function removeAllPermissionsFromUser(string $userId): void
    {
        EloquentModelPermission::where('model_id', $userId)
            ->where('model_name', 'User')
            ->delete();
    }

    /**
     * Get role IDs by role names
     */
    private function getRoleIdsByNames(array $roles): array
    {
        return EloquentRole::whereIn('name', $roles)
            ->pluck('id', 'name')
            ->toArray();
    }

    /**
     * Get permission IDs by permission names
     */
    private function getPermissionIdsByNames(array $permissions): array
    {
        return EloquentPermission::whereIn('name', $permissions)
            ->pluck('id', 'name')
            ->toArray();
    }


    // ==================== MAPPER METHODS ====================

    private function mapRoleToEntity(EloquentRole $model): RoleEntity
    {
        return new RoleEntity(
            id: $model->id,
            name: $model->name,
            guardName: $model->guard_name,
            isActive: $model->is_active,
            isDeleted: $model->is_deleted,
            createdAt: new DateTimeImmutable($model->created_at),
            updatedAt: new DateTimeImmutable($model->updated_at)
        );
    }

    private function mapPermissionToEntity(EloquentPermission $model): PermissionEntity
    {
        return new PermissionEntity(
            id: $model->id,
            name: $model->name,
            guardName: $model->guard_name,
            isActive: $model->is_active,
            isDeleted: $model->is_deleted,
            createdAt: new DateTimeImmutable($model->created_at),
            updatedAt: new DateTimeImmutable($model->updated_at)
        );
    }

    private function mapRolePermissionToEntity(EloquentRolePermission $model): RolePermissionEntity
    {
        return new RolePermissionEntity(
            id: $model->id,
            permissionId: $model->permission_id,
            roleId: $model->role_id,
            createdAt: new DateTimeImmutable($model->created_at),
            updatedAt: new DateTimeImmutable($model->updated_at),
            permission: $model->relationLoaded('permission') && $model->permission
                ? $this->mapPermissionToEntity($model->permission)
                : null,
            role: $model->relationLoaded('role') && $model->role
                ? $this->mapRoleToEntity($model->role)
                : null
        );
    }

    private function mapModelPermissionToEntity(EloquentModelPermission $model): ModelPermissionEntity
    {
        return new ModelPermissionEntity(
            id: $model->id,
            modelId: $model->model_id,
            permissionId: $model->permission_id,
            modelName: $model->model_name,
            createdAt: new DateTimeImmutable($model->created_at),
            updatedAt: new DateTimeImmutable($model->updated_at),
            permission: $model->relationLoaded('permission') && $model->permission
                ? $this->mapPermissionToEntity($model->permission)
                : null
        );
    }

    private function mapModelRoleToEntity(EloquentModelRole $model): ModelRoleEntity
    {
        return new ModelRoleEntity(
            id: $model->id,
            modelId: $model->model_id,
            roleId: $model->role_id,
            modelName: $model->model_name,
            createdAt: new DateTimeImmutable($model->created_at),
            updatedAt: new DateTimeImmutable($model->updated_at),
            role: $model->relationLoaded('role') && $model->role
                ? $this->mapRoleToEntity($model->role)
                : null
        );
    }


}
