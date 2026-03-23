<?php

namespace Modules\Shared\Application\Resources\Role;

use Modules\Shared\Domain\Entities\Role;
use Carbon\Carbon;

class RoleResource
{
    public string $id;
    public string $name;
    public string $guardName;
    public bool $isActive;
    public bool $isDeleted;
    public string $createdAt;
    public string $updatedAt;
    public array $permissions;

    public function __construct(Role $role, array $permissions = [])
    {
        $this->id = $role->id;
        $this->name = $role->name;
        $this->guardName = $role->guardName;
        $this->isActive = $role->isActive;
        $this->isDeleted = $role->isDeleted;
        $this->createdAt = Carbon::instance($role->createdAt)->toISOString();
        $this->updatedAt = Carbon::instance($role->updatedAt)->toISOString();
        $this->permissions = $permissions;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'guardName' => $this->guardName,
            'isActive' => $this->isActive,
            'isDeleted' => $this->isDeleted,
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt,
            'permissions' => $this->permissions,
        ];
    }

    public static function collection(array $roles, array $permissionsMap = []): array
    {
        return array_map(function($role) use ($permissionsMap) {
            $permissions = $permissionsMap[$role->id] ?? [];
            $resource = new self($role, $permissions);
            return $resource->toArray();
        }, $roles);
    }
}
