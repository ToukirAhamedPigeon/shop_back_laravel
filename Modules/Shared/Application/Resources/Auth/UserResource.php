<?php

namespace Modules\Shared\Application\Resources\Auth;

use Modules\Shared\Domain\Entities\User as UserEntity;

class UserResource
{
    public string $id;
    public string $name;
    public string $username;
    public string $email;
    public ?string $mobileNo;

    public bool $isActive;

    /** @var string[] */
    public array $roles;

    /** @var string[] */
    public array $permissions;

    // Profile
    public ?string $profileImage;
    public ?string $bio;
    public ?string $gender;
    public ?string $address;
    public ?string $dateOfBirth;

    // QR
    public ?string $qrCode;

    // Preferences
    public ?string $timezone;
    public ?string $language;

    // Audit / Auth info
    public ?string $lastLoginAt;
    public ?string $lastLoginIp;
    public ?string $emailVerifiedAt;

    public function __construct(UserEntity $user)
    {
        $this->id = $user->id;
        $this->name = $user->name;
        $this->username = $user->username;
        $this->email = $user->email;
        $this->mobileNo = $user->mobileNo;

        $this->isActive = $user->isActive;

        $this->roles = $user->roles ?? [];
        $this->permissions = $user->permissions ?? [];

        // Profile
        $this->profileImage = $user->profileImage;
        $this->bio = $user->bio;
        $this->gender = $user->gender;
        $this->address = $user->address;
        $this->dateOfBirth = $user->dateOfBirth?->format('Y-m-d');

        // QR
        $this->qrCode = $user->qrCode;

        // Preferences
        $this->timezone = $user->timezone;
        $this->language = $user->language;

        // Audit / Auth
        $this->lastLoginAt = $user->lastLoginAt?->format('Y-m-d H:i:s');
        $this->lastLoginIp = $user->lastLoginIp;
        $this->emailVerifiedAt = $user->emailVerifiedAt?->format('Y-m-d H:i:s');
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'username' => $this->username,
            'email' => $this->email,
            'mobile_no' => $this->mobileNo,

            'is_active' => $this->isActive,

            'roles' => $this->roles,
            'permissions' => $this->permissions,

            // Profile
            'profile_image' => $this->profileImage,
            'bio' => $this->bio,
            'gender' => $this->gender,
            'address' => $this->address,
            'date_of_birth' => $this->dateOfBirth,

            // QR
            'qr_code' => $this->qrCode,

            // Preferences
            'timezone' => $this->timezone,
            'language' => $this->language,

            // Audit / Auth
            'last_login_at' => $this->lastLoginAt,
            'last_login_ip' => $this->lastLoginIp,
            'email_verified_at' => $this->emailVerifiedAt,
        ];
    }
}
