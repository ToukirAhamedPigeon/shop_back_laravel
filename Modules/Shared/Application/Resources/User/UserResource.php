<?php

namespace Modules\Shared\Application\Resources\User;

use Modules\Shared\Domain\Entities\User;
use Carbon\Carbon;

class UserResource
{
    public string $id;
    public string $name;
    public string $username;
    public string $email;
    public ?string $mobileNo;

    public ?string $profileImage;
    public ?string $bio;
    public ?string $dateOfBirth;
    public ?string $gender;
    public ?string $address;

    public ?string $qrCode;
    public ?string $timezone;
    public ?string $language;

    public bool $isActive;
    public string $createdAt;
    public string $updatedAt;

    /** @var string[] */
    public array $roles;

    /** @var string[] */
    public array $permissions;

    public function __construct(User $user)
    {
        $this->id = $user->id;
        $this->name = $user->name;
        $this->username = $user->username;
        $this->email = $user->email;
        $this->mobileNo = $user->mobileNo;

        $this->profileImage = $user->profileImage;
        $this->bio = $user->bio;
        $this->dateOfBirth = $user->dateOfBirth
            ? Carbon::instance($user->dateOfBirth)->toISOString()
            : null;

        $this->gender = $user->gender;
        $this->address = $user->address;

        $this->qrCode = $user->qrCode;
        $this->timezone = $user->timezone;
        $this->language = $user->language;

        $this->isActive = $user->isActive;
        $this->createdAt = Carbon::instance($user->createdAt)->toISOString();
        $this->updatedAt = Carbon::instance($user->updatedAt)->toISOString();

        // ✅ Already arrays in Domain
        $this->roles = $user->roles;
        $this->permissions = $user->permissions;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'username' => $this->username,
            'email' => $this->email,
            'mobileNo' => $this->mobileNo,

            'profileImage' => $this->profileImage,
            'bio' => $this->bio,
            'dateOfBirth' => $this->dateOfBirth,
            'gender' => $this->gender,
            'address' => $this->address,

            'qrCode' => $this->qrCode,
            'timezone' => $this->timezone,
            'language' => $this->language,

            'isActive' => $this->isActive,
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt,

            'roles' => $this->roles,
            'permissions' => $this->permissions,
        ];
    }
}
