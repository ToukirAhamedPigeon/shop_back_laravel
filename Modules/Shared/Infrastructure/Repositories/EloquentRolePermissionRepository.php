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
use Modules\Shared\Application\Requests\Role\RoleFilterRequest;
use Modules\Shared\Application\Requests\Permission\PermissionFilterRequest;
use Modules\Shared\Application\Resources\Role\RoleResource;
use Modules\Shared\Application\Resources\Permission\PermissionResource;
use Carbon\Carbon;

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
        $model->deleted_at = $role->deletedAt?->format('Y-m-d H:i:s');
        $model->created_at = $role->createdAt->format('Y-m-d H:i:s');
        $model->updated_at = $role->updatedAt->format('Y-m-d H:i:s');
        $model->created_by = $role->createdBy;
        $model->updated_by = $role->updatedBy;
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
        $model->deleted_at = $role->deletedAt?->format('Y-m-d H:i:s');
        $model->updated_at = $role->updatedAt->format('Y-m-d H:i:s');
        $model->updated_by = $role->updatedBy;
        $model->save();

        return $this->mapRoleToEntity($model);
    }

    public function deleteRole(string $id, bool $permanent = false, ?string $deletedBy = null): void
    {
        $model = EloquentRole::find($id);
        if (!$model) return;

        if ($permanent) {
            // Delete related records
            EloquentRolePermission::where('role_id', $id)->delete();
            DB::table('model_roles')->where('role_id', $id)->delete();
            $model->forceDelete();
        } else {
            // Soft delete - just set is_deleted flag
            $model->is_deleted = true;
            $model->deleted_at = now();
            $model->updated_at = now();
            $model->updated_by = $deletedBy;
            $model->save();
        }
    }

    public function restoreRole(string $id): void
    {
        $model = EloquentRole::where('id', $id)->first();
        if ($model) {
            $model->is_deleted = false;
            $model->deleted_at = null;
            $model->save();
        }
    }

    public function roleHasRelatedRecords(string $roleId): bool
    {
        $hasRolePermissions = EloquentRolePermission::where('role_id', $roleId)->exists();
        $hasModelRoles = DB::table('model_roles')->where('role_id', $roleId)->exists();
        return $hasRolePermissions || $hasModelRoles;
    }

    public function getFilteredRoles(RoleFilterRequest $request): array
    {
        // Get the raw values from the request
        $isDeletedStr = $request->input('isDeletedStr', 'false');
        $isActiveStr = $request->input('isActiveStr', 'all');
        $permissions = $request->input('permissions', []);
        $q = $request->input('q', '');
        $page = (int) $request->input('page', 1);
        $limit = (int) $request->input('limit', 10);
        $sortBy = $request->input('sortBy', 'createdAt');
        $sortOrder = $request->input('sortOrder', 'desc');

        // Log for debugging
        Log::info('getFilteredRoles - isDeletedStr: ' . $isDeletedStr);
        Log::info('getFilteredRoles - isActiveStr: ' . $isActiveStr);
        Log::info('getFilteredRoles - permissions: ' . json_encode($permissions));
        Log::info('getFilteredRoles - page: ' . $page . ', limit: ' . $limit);

        $query = EloquentRole::query();

        // Handle deleted filter based on the raw string value
        if ($isDeletedStr === 'true') {
            // Show ONLY deleted records (soft deleted)
            $query->where('is_deleted', true);
            Log::info('Filtering: show deleted only');
        } elseif ($isDeletedStr === 'false') {
            // Show ONLY non-deleted records
            $query->where('is_deleted', false);
            Log::info('Filtering: show non-deleted only');
        } else {
            Log::info('Filtering: show all (isDeletedStr: ' . $isDeletedStr . ')');
        }

        // Handle active filter based on the raw string value
        if ($isActiveStr === 'true') {
            $query->where('is_active', true);
            Log::info('Filtering: active only');
        } elseif ($isActiveStr === 'false') {
            $query->where('is_active', false);
            Log::info('Filtering: inactive only');
        } elseif ($isActiveStr === 'all') {
            Log::info('Filtering: all active status');
        }

        // Handle permissions filter - filter roles by associated permissions
        if (!empty($permissions)) {
            $query->whereHas('rolePermissions.permission', function($qry) use ($permissions) {
                $qry->whereIn('name', $permissions);
            });
            Log::info('Filtering by permissions: ' . json_encode($permissions));
        }

        // Handle search
        if (!empty($q)) {
            $query->where(function($qry) use ($q) {
                $qry->where('name', 'like', "%{$q}%")
                    ->orWhere('guard_name', 'like', "%{$q}%");
            });
            Log::info('Searching for: ' . $q);
        }

        // Get total count (count of filtered records)
        $totalCount = $query->count();

        // Get grand total count (count of ALL records including deleted)
        $grandTotalCount = EloquentRole::count() + EloquentRole::onlyDeleted()->count();

        // Handle sorting - map to actual column names
        $sortColumn = match ($sortBy) {
            'name' => 'name',
            'guardname' => 'guard_name',
            'isactive' => 'is_active',
            'createdat' => 'created_at',
            'updatedat' => 'updated_at',
            default => 'created_at',
        };

        Log::info('Sorting by: ' . $sortColumn . ' ' . $sortOrder);

        $query->orderBy($sortColumn, $sortOrder);

        // Pagination
        $roles = $query->skip(($page - 1) * $limit)
            ->take($limit)
            ->get();

        Log::info('Query returned: ' . count($roles) . ' roles');

        // Build DTOs with permissions
        $result = [];
        $permissionsMap = [];

        foreach ($roles as $role) {
            $perms = $this->getPermissionsByRoleId($role->id);
            $permissionsMap[$role->id] = $perms;
            $result[] = $this->mapRoleToEntity($role);
        }

        return [
            'roles' => RoleResource::collection($result, $permissionsMap),
            'totalCount' => $totalCount,
            'grandTotalCount' => $grandTotalCount,
            'pageIndex' => $page - 1,
            'pageSize' => $limit,
        ];
    }

    public function getAllRolesPaginated(RoleFilterRequest $request): array
    {
        return $this->getFilteredRoles($request);
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
        $model->deleted_at = $permission->deletedAt?->format('Y-m-d H:i:s');
        $model->created_at = $permission->createdAt->format('Y-m-d H:i:s');
        $model->updated_at = $permission->updatedAt->format('Y-m-d H:i:s');
        $model->created_by = $permission->createdBy;
        $model->updated_by = $permission->updatedBy;
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
        $model->deleted_at = $permission->deletedAt?->format('Y-m-d H:i:s');
        $model->updated_at = $permission->updatedAt->format('Y-m-d H:i:s');
        $model->updated_by = $permission->updatedBy;
        $model->save();

        return $this->mapPermissionToEntity($model);
    }

    public function deletePermission(string $id, bool $permanent = false, ?string $deletedBy = null): void
    {
        $model = EloquentPermission::find($id);
        if (!$model) return;

        if ($permanent) {
            // Delete related records
            EloquentRolePermission::where('permission_id', $id)->delete();
            DB::table('model_permissions')->where('permission_id', $id)->delete();
            $model->forceDelete();
        } else {
            // Soft delete - just set is_deleted flag
            $model->is_deleted = true;
            $model->deleted_at = now();
            $model->updated_at = now();
            $model->updated_by = $deletedBy;
            $model->save();
        }
    }

    public function restorePermission(string $id): void
    {
        $model = EloquentPermission::where('id', $id)->first();
        if ($model) {
            $model->is_deleted = false;
            $model->deleted_at = null;
            $model->save();
        }
    }

    public function permissionHasRelatedRecords(string $permissionId): bool
    {
        $hasRolePermissions = EloquentRolePermission::where('permission_id', $permissionId)->exists();
        $hasModelPermissions = DB::table('model_permissions')->where('permission_id', $permissionId)->exists();
        return $hasRolePermissions || $hasModelPermissions;
    }

    public function getFilteredPermissions(PermissionFilterRequest $request): array
    {
        // Get the raw values from the request
        $isDeletedStr = $request->input('isDeletedStr', 'false');
        $isActiveStr = $request->input('isActiveStr', 'all');
        $roles = $request->input('roles', []);
        $q = $request->input('q', '');
        $page = (int) $request->input('page', 1);
        $limit = (int) $request->input('limit', 10);
        $sortBy = $request->input('sortBy', 'createdAt');
        $sortOrder = $request->input('sortOrder', 'desc');

        // Log for debugging
        Log::info('getFilteredPermissions - isDeletedStr: ' . $isDeletedStr);
        Log::info('getFilteredPermissions - isActiveStr: ' . $isActiveStr);
        Log::info('getFilteredPermissions - roles: ' . json_encode($roles));
        Log::info('getFilteredPermissions - page: ' . $page . ', limit: ' . $limit);

        $query = EloquentPermission::query();

        // Handle deleted filter based on the raw string value
        if ($isDeletedStr === 'true') {
            // Show ONLY deleted records (soft deleted)
            $query->where('is_deleted', true);
            Log::info('Filtering: show deleted only');
        } elseif ($isDeletedStr === 'false') {
            // Show ONLY non-deleted records
            $query->where('is_deleted', false);
            Log::info('Filtering: show non-deleted only');
        } else {
            Log::info('Filtering: show all (isDeletedStr: ' . $isDeletedStr . ')');
        }

        // Handle active filter based on the raw string value
        if ($isActiveStr === 'true') {
            $query->where('is_active', true);
            Log::info('Filtering: active only');
        } elseif ($isActiveStr === 'false') {
            $query->where('is_active', false);
            Log::info('Filtering: inactive only');
        } elseif ($isActiveStr === 'all') {
            Log::info('Filtering: all active status');
        }

        // Handle roles filter - filter permissions by associated roles
        if (!empty($roles)) {
            $query->whereHas('rolePermissions.role', function($qry) use ($roles) {
                $qry->whereIn('name', $roles);
            });
            Log::info('Filtering by roles: ' . json_encode($roles));
        }

        // Handle search
        if (!empty($q)) {
            $query->where(function($qry) use ($q) {
                $qry->where('name', 'like', "%{$q}%")
                    ->orWhere('guard_name', 'like', "%{$q}%");
            });
            Log::info('Searching for: ' . $q);
        }

        // Get total count (count of filtered records)
        $totalCount = $query->count();

        // Get grand total count (count of ALL records including deleted)
        $grandTotalCount = EloquentPermission::count() + EloquentPermission::onlyDeleted()->count();

        // Handle sorting - map to actual column names
        $sortColumn = match ($sortBy) {
            'name' => 'name',
            'guardname' => 'guard_name',
            'isactive' => 'is_active',
            'createdat' => 'created_at',
            'updatedat' => 'updated_at',
            default => 'created_at',
        };

        Log::info('Sorting by: ' . $sortColumn . ' ' . $sortOrder);

        $query->orderBy($sortColumn, $sortOrder);

        // Pagination
        $permissions = $query->skip(($page - 1) * $limit)
            ->take($limit)
            ->get();

        Log::info('Query returned: ' . count($permissions) . ' permissions');

        // Build DTOs with roles
        $result = [];
        $rolesMap = [];

        foreach ($permissions as $permission) {
            $roleList = $this->getRolesByPermissionId($permission->id);
            $rolesMap[$permission->id] = $roleList;
            $result[] = $this->mapPermissionToEntity($permission);
        }

        return [
            'permissions' => PermissionResource::collection($result, $rolesMap),
            'totalCount' => $totalCount,
            'grandTotalCount' => $grandTotalCount,
            'pageIndex' => $page - 1,
            'pageSize' => $limit,
        ];
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

    public function assignPermissionsToRole(string $roleId, array $permissionNames): void
    {
        // Remove existing permissions
        EloquentRolePermission::where('role_id', $roleId)->delete();

        if (!empty($permissionNames)) {
            $permissions = EloquentPermission::whereIn('name', $permissionNames)
                ->where('is_deleted', false)
                ->where('is_active', true)
                ->get();

            foreach ($permissions as $permission) {
                EloquentRolePermission::create([
                    'id' => (string) Str::uuid(),
                    'role_id' => $roleId,
                    'permission_id' => $permission->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function assignRolesToPermission(string $permissionId, array $roleNames): void
    {
        // Remove existing associations
        EloquentRolePermission::where('permission_id', $permissionId)->delete();

        if (!empty($roleNames)) {
            $roles = EloquentRole::whereIn('name', $roleNames)
                ->where('is_deleted', false)
                ->where('is_active', true)
                ->get();

            foreach ($roles as $role) {
                EloquentRolePermission::create([
                    'id' => (string) Str::uuid(),
                    'role_id' => $role->id,
                    'permission_id' => $permissionId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
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

    public function getPermissionsByRoleNames(array $roleNames): array
    {
        if (empty($roleNames)) {
            return [];
        }

        return EloquentRole::whereIn('name', $roleNames)
            ->with('rolePermissions.permission')
            ->get()
            ->flatMap(function ($role) {
                return $role->rolePermissions->pluck('permission.name');
            })
            ->unique()
            ->toArray();
    }

    public function getRolesByPermissionNames(array $permissionNames): array
    {
        if (empty($permissionNames)) {
            return [];
        }

        return EloquentPermission::whereIn('name', $permissionNames)
            ->with('rolePermissions.role')
            ->get()
            ->flatMap(function ($permission) {
                return $permission->rolePermissions->pluck('role.name');
            })
            ->unique()
            ->toArray();
    }

    // ==================== GET ALL METHODS ====================

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

    public function getAllRolesAsync(): array
    {
        return $this->getAllRoles();
    }

    public function getAllPermissionsAsync(): array
    {
        return $this->getAllPermissions();
    }

    // ==================== USER ROLE/PERMISSION ASSIGNMENT METHODS ====================

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

    public function setRolesForUser(string $userId, array $roles): void
    {
        $this->removeAllRolesFromUser($userId);
        $this->assignRolesToUser($userId, $roles);
    }

    public function setPermissionsForUser(string $userId, array $permissions): void
    {
        $this->removeAllPermissionsFromUser($userId);
        $this->assignPermissionsToUser($userId, $permissions);
    }

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

    public function removeAllRolesFromUser(string $userId): void
    {
        EloquentModelRole::where('model_id', $userId)
            ->where('model_name', 'User')
            ->delete();
    }

    public function removeAllPermissionsFromUser(string $userId): void
    {
        EloquentModelPermission::where('model_id', $userId)
            ->where('model_name', 'User')
            ->delete();
    }

    // ==================== ROLE/PERMISSION EXISTS METHODS ====================

    public function roleExists(string $name, ?string $ignoreId = null): bool
    {
        $query = EloquentRole::where('name', $name)->where('is_deleted', false);
        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }
        return $query->exists();
    }

    public function permissionExists(string $name, ?string $ignoreId = null): bool
    {
        $query = EloquentPermission::where('name', $name)->where('is_deleted', false);
        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }
        return $query->exists();
    }

    public function getRoleById(string $id): ?RoleEntity
    {
        $model = EloquentRole::find($id);
        return $model ? $this->mapRoleToEntity($model) : null;
    }

    public function getPermissionById(string $id): ?PermissionEntity
    {
        $model = EloquentPermission::find($id);
        return $model ? $this->mapPermissionToEntity($model) : null;
    }

    // ==================== SAVE CHANGES METHODS ====================

    public function saveChanges(): void
    {
        // This method is not needed in Eloquent as changes are auto-saved
        // But kept for interface compatibility
    }

    // ==================== MAPPER METHODS ====================

    private function mapRoleToEntity(EloquentRole $model): RoleEntity
    {
        return new RoleEntity(
            id: $model->id,
            name: $model->name,
            guardName: $model->guard_name,
            isActive: (bool) $model->is_active,
            isDeleted: (bool) $model->is_deleted,
            deletedAt: $model->deleted_at ? new DateTimeImmutable($model->deleted_at) : null,
            createdAt: new DateTimeImmutable($model->created_at),
            updatedAt: new DateTimeImmutable($model->updated_at),
            createdBy: $model->created_by,
            updatedBy: $model->updated_by
        );
    }

    private function mapPermissionToEntity(EloquentPermission $model): PermissionEntity
    {
        return new PermissionEntity(
            id: $model->id,
            name: $model->name,
            guardName: $model->guard_name,
            isActive: (bool) $model->is_active,
            isDeleted: (bool) $model->is_deleted,
            deletedAt: $model->deleted_at ? new DateTimeImmutable($model->deleted_at) : null,
            createdAt: new DateTimeImmutable($model->created_at),
            updatedAt: new DateTimeImmutable($model->updated_at),
            createdBy: $model->created_by,
            updatedBy: $model->updated_by
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
