<?php

namespace Modules\Shared\Application\Resources\Permission;

use Modules\Shared\Domain\Entities\Permission;
use Carbon\Carbon;

class PermissionResource
{
    public string $id;
    public string $name;
    public string $guardName;
    public bool $isActive;
    public bool $isDeleted;
    public string $createdAt;
    public string $updatedAt;
    public array $roles;

    public function __construct(Permission $permission, array $roles = [])
    {
        $this->id = $permission->id;
        $this->name = $permission->name;
        $this->guardName = $permission->guardName;
        $this->isActive = $permission->isActive;
        $this->isDeleted = $permission->isDeleted;
        $this->createdAt = Carbon::instance($permission->createdAt)->toISOString();
        $this->updatedAt = Carbon::instance($permission->updatedAt)->toISOString();
        $this->roles = $roles;
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
            'roles' => $this->roles,
        ];
    }

    public static function collection(array $permissions, array $rolesMap = []): array
    {
        return array_map(function($permission) use ($rolesMap) {
            $roles = $rolesMap[$permission->id] ?? [];
            $resource = new self($permission, $roles);
            return $resource->toArray();
        }, $permissions);
    }
}
