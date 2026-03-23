<?php

namespace Modules\Shared\Application\Repositories;

use Modules\Shared\Domain\Entities\Role;
use Modules\Shared\Domain\Entities\Permission;
use Modules\Shared\Domain\Entities\RolePermission;
use Modules\Shared\Domain\Entities\ModelPermission;
use Modules\Shared\Domain\Entities\ModelRole;
use Modules\Shared\Application\Requests\Role\RoleFilterRequest;
use Modules\Shared\Application\Requests\Permission\PermissionFilterRequest;

interface IRolePermissionRepository
{
    // ==================== EXISTING METHODS ====================
    public function findRoleById(string $id): ?Role;
    public function findRoleByName(string $name): ?Role;
    public function findAllRoles(array $filters = []): array;

    public function findPermissionById(string $id): ?Permission;
    public function findPermissionByName(string $name): ?Permission;
    public function findAllPermissions(array $filters = []): array;

    public function findRolePermissionById(string $id): ?RolePermission;
    public function findRolePermissionByRoleAndPermission(string $roleId, string $permissionId): ?RolePermission;
    public function findAllRolePermissionsByRoleId(string $roleId): array;
    public function findAllRolePermissionsByPermissionId(string $permissionId): array;
    public function createRolePermission(RolePermission $rolePermission): RolePermission;
    public function deleteRolePermission(string $id): void;
    public function deleteRolePermissionsByRole(string $roleId): void;

    public function findModelPermissionById(string $id): ?ModelPermission;
    public function findAllModelPermissionsByModel(string $modelId, string $modelName = 'User'): array;
    public function createModelPermission(ModelPermission $modelPermission): ModelPermission;
    public function deleteModelPermission(string $id): void;
    public function deleteModelPermissionsByModel(string $modelId, string $modelName = 'User'): void;

    public function findModelRoleById(string $id): ?ModelRole;
    public function findAllModelRolesByModel(string $modelId, string $modelName = 'User'): array;
    public function createModelRole(ModelRole $modelRole): ModelRole;
    public function deleteModelRole(string $id): void;
    public function deleteModelRolesByModel(string $modelId, string $modelName = 'User'): void;

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

    // ==================== NEW METHODS FOR USER SERVICE ====================

    /**
     * Assign roles to a user
     */
    public function assignRolesToUser(string $userId, array $roles): void;

    /**
     * Assign permissions directly to a user
     */
    public function assignPermissionsToUser(string $userId, array $permissions): void;

    /**
     * Set roles for a user (replaces existing ones)
     */
    public function setRolesForUser(string $userId, array $roles): void;

    /**
     * Set permissions for a user (replaces existing direct permissions)
     */
    public function setPermissionsForUser(string $userId, array $permissions): void;

    /**
     * Validate that all role names exist
     *
     * @return array Array of valid role names
     */
    public function validateRolesExist(array $roles): array;


    /**
     * Remove all roles from a user
     */
    public function removeAllRolesFromUser(string $userId): void;

    /**
     * Remove all direct permissions from a user
     */
    public function removeAllPermissionsFromUser(string $userId): void;
     // ==================== ROLE CRUD METHODS ====================

    /**
     * Get filtered roles with pagination
     */
    public function getFilteredRoles(RoleFilterRequest $request): array;

    /**
     * Get role by ID with permissions
     */
    public function getRoleById(string $id): ?Role;

    /**
     * Check if role exists by name
     */
    public function roleExists(string $name, ?string $ignoreId = null): bool;

    /**
     * Create a new role
     */
    public function createRole(Role $role): Role;

    /**
     * Update an existing role
     */
    public function updateRole(Role $role): Role;

    /**
     * Delete a role (soft or hard)
     */
    public function deleteRole(string $id, bool $permanent = false, ?string $deletedBy = null): void;

    /**
     * Restore a soft-deleted role
     */
    public function restoreRole(string $id): void;

    /**
     * Check if role has related records
     */
    public function roleHasRelatedRecords(string $roleId): bool;

    /**
     * Assign permissions to a role
     */
    public function assignPermissionsToRole(string $roleId, array $permissionNames): void;

    /**
     * Get permission names by role names
     */
    public function getPermissionsByRoleNames(array $roleNames): array;

    /**
     * Get all roles with pagination
     */
    public function getAllRolesPaginated(RoleFilterRequest $request): array;

    // ==================== PERMISSION CRUD METHODS ====================

    /**
     * Get filtered permissions with pagination
     */
    public function getFilteredPermissions(PermissionFilterRequest $request): array;

    /**
     * Get permission by ID with roles
     */
    public function getPermissionById(string $id): ?Permission;

    /**
     * Check if permission exists by name
     */
    public function permissionExists(string $name, ?string $ignoreId = null): bool;

    /**
     * Create a new permission
     */
    public function createPermission(Permission $permission): Permission;

    /**
     * Update an existing permission
     */
    public function updatePermission(Permission $permission): Permission;

    /**
     * Delete a permission (soft or hard)
     */
    public function deletePermission(string $id, bool $permanent = false, ?string $deletedBy = null): void;

    /**
     * Restore a soft-deleted permission
     */
    public function restorePermission(string $id): void;

    /**
     * Check if permission has related records
     */
    public function permissionHasRelatedRecords(string $permissionId): bool;

    /**
     * Assign roles to a permission
     */
    public function assignRolesToPermission(string $permissionId, array $roleNames): void;

    /**
     * Get role names by permission names
     */
    public function getRolesByPermissionNames(array $permissionNames): array;
}
