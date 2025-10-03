<?php

namespace Modules\Shared\Infrastructure\Repositories;

use Modules\Shared\Application\Repositories\IRolePermissionRepository;
use Modules\Shared\Infrastructure\Models\EloquentRolePermission;
use Modules\Shared\Infrastructure\Models\EloquentModelRole;
use Modules\Shared\Infrastructure\Models\EloquentModelPermission;
use Modules\Shared\Infrastructure\Models\EloquentPermission;
use Modules\Shared\Infrastructure\Models\EloquentRole;
use Modules\Shared\Domain\Entities\RolePermission;

class EloquentRolePermissionRepository implements IRolePermissionRepository
{
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

    public function getRoleNamesByUserId(string $userId,string $model_name="User"): array
    {
        return EloquentModelRole::where('model_id', $userId)
            ->where('model_name', $model_name)
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
        return array_unique(array_merge(
            $this->getRolePermissionsByUserId($userId),
            $this->getModelPermissionsByUserId($userId)
        ));
    }
    public function findById(string $id): ?RolePermission
    {
        $model = EloquentRolePermission::find($id);
        return $model ? $this->mapModelToEntity($model) : null;
    }

    public function findByRoleAndPermission(string $roleId, string $permissionId): ?RolePermission
    {
        $model = EloquentRolePermission::where('role_id', $roleId)
            ->where('permission_id', $permissionId)
            ->first();

        return $model ? $this->mapModelToEntity($model) : null;
    }

    public function create(RolePermission $rolePermission): RolePermission
    {
        EloquentRolePermission::create([
            'id' => $rolePermission->id,
            'role_id' => $rolePermission->roleId,
            'permission_id' => $rolePermission->permissionId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $rolePermission;
    }

    public function delete(RolePermission $rolePermission): void
    {
        EloquentRolePermission::where('id', $rolePermission->id)->delete();
    }

    // --- Helper to map Eloquent model to domain entity ---
    private function mapModelToEntity(EloquentRolePermission $model): RolePermission
    {
        return new RolePermission(
            $model->id,
            $model->permission_id,
            $model->role_id,
            $model->created_at,
            $model->updated_at
        );
    }
}
