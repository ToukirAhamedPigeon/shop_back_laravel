<?php

namespace Modules\Shared\Application\Services;

use Modules\Shared\Application\Requests\Role\RoleFilterRequest;
use Modules\Shared\Application\Requests\Role\CreateRoleRequest;
use Modules\Shared\Application\Requests\Role\UpdateRoleRequest;

interface IRoleService
{
    public function getRoles(RoleFilterRequest $request): array;
    public function getRole(string $id): ?array;
    public function getRoleForEdit(string $id): ?array;
    public function createRole(CreateRoleRequest $request, ?string $createdBy): array;
    public function updateRole(string $id, UpdateRoleRequest $request, ?string $updatedBy): array;
    public function deleteRole(string $id, bool $permanent, ?string $currentUserId): array;
    public function restoreRole(string $id, ?string $currentUserId): array;
    public function checkDeleteEligibility(string $id): array;
}
