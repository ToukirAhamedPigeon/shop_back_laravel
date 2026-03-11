<?php

namespace Modules\Shared\Application\Repositories;

use Modules\Shared\Domain\Entities\Role;
use Modules\Shared\Domain\Entities\Permission;
use Modules\Shared\Domain\Entities\RolePermission;
use Modules\Shared\Domain\Entities\ModelPermission;
use Modules\Shared\Domain\Entities\ModelRole;

interface IRolePermissionRepository
{
    // ==================== ROLE METHODS ====================
    public function findRoleById(string $id): ?Role;
    public function findRoleByName(string $name): ?Role;
    public function findAllRoles(array $filters = []): array;
    public function createRole(Role $role): Role;
    public function updateRole(Role $role): Role;
    public function deleteRole(string $id): void;
    public function restoreRole(string $id): void;

    // ==================== PERMISSION METHODS ====================
    public function findPermissionById(string $id): ?Permission;
    public function findPermissionByName(string $name): ?Permission;
    public function findAllPermissions(array $filters = []): array;
    public function createPermission(Permission $permission): Permission;
    public function updatePermission(Permission $permission): Permission;
    public function deletePermission(string $id): void;
    public function restorePermission(string $id): void;

    // ==================== ROLE-PERMISSION METHODS ====================
    public function findRolePermissionById(string $id): ?RolePermission;
    public function findRolePermissionByRoleAndPermission(string $roleId, string $permissionId): ?RolePermission;
    public function findAllRolePermissionsByRoleId(string $roleId): array;
    public function findAllRolePermissionsByPermissionId(string $permissionId): array;
    public function createRolePermission(RolePermission $rolePermission): RolePermission;
    public function deleteRolePermission(string $id): void;
    public function deleteRolePermissionsByRole(string $roleId): void;

    // ==================== MODEL-PERMISSION METHODS ====================
    public function findModelPermissionById(string $id): ?ModelPermission;
    public function findAllModelPermissionsByModel(string $modelId, string $modelName = 'User'): array;
    public function createModelPermission(ModelPermission $modelPermission): ModelPermission;
    public function deleteModelPermission(string $id): void;
    public function deleteModelPermissionsByModel(string $modelId, string $modelName = 'User'): void;

    // ==================== MODEL-ROLE METHODS ====================
    public function findModelRoleById(string $id): ?ModelRole;
    public function findAllModelRolesByModel(string $modelId, string $modelName = 'User'): array;
    public function createModelRole(ModelRole $modelRole): ModelRole;
    public function deleteModelRole(string $id): void;
    public function deleteModelRolesByModel(string $modelId, string $modelName = 'User'): void;

    // ==================== USER PERMISSION HELPER METHODS ====================
    public function getPermissionsByRoleId(string $roleId): array;
    public function getRolesByPermissionId(string $permissionId): array;
    public function getRoleNamesByUserId(string $userId, string $modelName = "User"): array;
    public function getRolePermissionsByUserId(string $userId): array;
    public function getModelPermissionsByUserId(string $userId): array;
    public function getAllPermissionsByUserId(string $userId): array;
    
    public function getAllRoles(): array;
    public function getAllRolesAsync(): array;
    public function getAllPermissions(): array;
    public function getAllPermissionsAsync(): array;
}
