<?php

namespace Modules\Shared\Application\Resources\Auth;

use Modules\Shared\Domain\Entities\User as UserEntity;

class UserResource
{
    public string $id;
    public string $username;
    public string $email;
    public string $mobileNo;
    public bool $isActive;
    public array $roles;
    public array $permissions;

    public function __construct(UserEntity $user)
    {
        $this->id = $user->id;
        $this->username = $user->username;
        $this->email = $user->email;
        $this->mobileNo = $user->mobileNo;
        $this->isActive = $user->isActive;
        $this->roles = $user->roles ?? [];
        $this->permissions = $user->permissions ?? [];
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email,
            'mobile_no' => $this->mobileNo,
            'is_active' => $this->isActive,
            'roles' => $this->roles,
            'permissions' => $this->permissions,
        ];
    }
}
