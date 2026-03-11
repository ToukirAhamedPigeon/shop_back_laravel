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
    public ?string $emailVerifiedAt;
    public ?string $mobileNo;

    // Profile
    public ?string $profileImage;
    public ?string $bio;
    public ?string $dateOfBirth;
    public ?string $gender;
    public ?string $address;

    // QR
    public ?string $qrCode;

    // Preferences
    public ?string $timezone;
    public ?string $nid;
    public ?string $language;

    // Status
    public bool $isActive;
    public bool $isDeleted;

    // Audit
    public string $createdAt;
    public string $updatedAt;
    public ?string $createdByName;
    public ?string $updatedByName;

    /** @var string[] */
    public array $roles;

    /** @var string[] */
    public array $permissions;

    public function __construct(User $user, ?string $createdByName = null, ?string $updatedByName = null)
    {
        $this->id = $user->id;
        $this->name = $user->name;
        $this->username = $user->username;
        $this->email = $user->email;
        $this->emailVerifiedAt = $user->emailVerifiedAt
            ? Carbon::instance($user->emailVerifiedAt)->toISOString()
            : null;
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
        $this->nid = $user->nid;
        $this->language = $user->language;

        $this->isActive = $user->isActive;
        $this->isDeleted = $user->isDeleted;

        $this->createdAt = Carbon::instance($user->createdAt)->toISOString();
        $this->updatedAt = Carbon::instance($user->updatedAt)->toISOString();
        $this->createdByName = $createdByName;
        $this->updatedByName = $updatedByName;

        $this->roles = $user->roles ?? [];
        $this->permissions = $user->permissions ?? [];
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'username' => $this->username,
            'email' => $this->email,
            'emailVerifiedAt' => $this->emailVerifiedAt,
            'mobileNo' => $this->mobileNo,

            'profileImage' => $this->profileImage,
            'bio' => $this->bio,
            'dateOfBirth' => $this->dateOfBirth,
            'gender' => $this->gender,
            'address' => $this->address,

            'qrCode' => $this->qrCode,
            'timezone' => $this->timezone,
            'nid' => $this->nid,
            'language' => $this->language,

            'isActive' => $this->isActive,
            'isDeleted' => $this->isDeleted,

            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt,
            'createdByName' => $this->createdByName,
            'updatedByName' => $this->updatedByName,

            'roles' => $this->roles,
            'permissions' => $this->permissions,
        ];
    }

    public static function collection(array $users, array $createdByNames = [], array $updatedByNames = []): array
    {
        return array_map(function($user) use ($createdByNames, $updatedByNames) {
            // ✅ FIX: Create object first, then call toArray()
            $resource = new self(
                $user,
                $createdByNames[$user->createdBy] ?? null,
                $updatedByNames[$user->updatedBy] ?? null
            );
            return $resource->toArray();
        }, $users);
    }
}
