<?php

namespace Modules\Shared\Application\Repositories;

use Modules\Shared\Domain\Entities\RolePermission;

interface IRolePermissionRepository
{
    public function getPermissionsByRoleId(string $roleId): array;
    public function getRolesByPermissionId(string $permissionId): array;
    public function getRoleNamesByUserId(string $userId): array;
    public function getRolePermissionsByUserId(string $userId): array;
    public function getModelPermissionsByUserId(string $userId): array;
    public function getAllPermissionsByUserId(string $userId): array;
    public function findById(string $id): ?RolePermission;
    public function findByRoleAndPermission(string $roleId, string $permissionId): ?RolePermission;
    public function create(RolePermission $rolePermission): RolePermission;
    public function delete(RolePermission $rolePermission): void;

}
