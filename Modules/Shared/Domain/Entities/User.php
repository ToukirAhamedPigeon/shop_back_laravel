<?php

namespace Modules\Shared\Domain\Entities;

use DateTimeImmutable;

final class User
{
    // --------------------
    // Identity
    // --------------------
    public string $id;
    public string $name;
    public string $username;
    public string $email;
    public string $password;

    // --------------------
    // Profile (Nullable)
    // --------------------
    public ?string $profileImage;
    public ?string $bio;
    public ?DateTimeImmutable $dateOfBirth;
    public ?string $gender;
    public ?string $address;

    // --------------------
    // Contact & Verification
    // --------------------
    public ?string $mobileNo;
    public ?DateTimeImmutable $emailVerifiedAt;

    // --------------------
    // QR
    // --------------------
    public ?string $qrCode;

    // --------------------
    // Auth & Security
    // --------------------
    public ?string $rememberToken;
    public ?DateTimeImmutable $lastLoginAt;
    public ?string $lastLoginIp;

    // --------------------
    // Preferences
    // --------------------
    public ?string $timezone;
    public ?string $language;

    // --------------------
    // Flags
    // --------------------
    public bool $isActive;
    public bool $isDeleted;
    public ?DateTimeImmutable $deletedAt;

    // --------------------
    // Audit
    // --------------------
    public DateTimeImmutable $createdAt;
    public DateTimeImmutable $updatedAt;
    public ?string $createdBy;
    public ?string $updatedBy;

    /** @var RefreshToken[] */
    public array $refreshTokens = [];

    /** @var string[] */
    public array $roles = [];

    /** @var string[] */
    public array $permissions = [];

    public function __construct(
        string $id,
        string $name,
        string $username,
        string $email,
        string $password,

        ?string $profileImage = null,
        ?string $bio = null,
        ?DateTimeImmutable $dateOfBirth = null,
        ?string $gender = null,
        ?string $address = null,

        ?string $mobileNo = null,
        ?DateTimeImmutable $emailVerifiedAt = null,

        ?string $qrCode = null,

        ?string $rememberToken = null,
        ?DateTimeImmutable $lastLoginAt = null,
        ?string $lastLoginIp = null,

        ?string $timezone = null,
        ?string $language = null,

        bool $isActive = true,
        bool $isDeleted = false,
        ?DateTimeImmutable $deletedAt = null,

        ?DateTimeImmutable $createdAt = null,
        ?DateTimeImmutable $updatedAt = null,
        ?string $createdBy = null,
        ?string $updatedBy = null,

        array $refreshTokens = [],
        array $roles = [],
        array $permissions = []
    ) {
         $this->id = $id;
        $this->name = $name;
        $this->username = $username;
        $this->email = $email;
        $this->password = $password;

        $this->profileImage = $profileImage;
        $this->bio = $bio;
        $this->dateOfBirth = $dateOfBirth;
        $this->gender = $gender;
        $this->address = $address;

        $this->mobileNo = $mobileNo;
        $this->emailVerifiedAt = $emailVerifiedAt;

        $this->qrCode = $qrCode;

        $this->rememberToken = $rememberToken;
        $this->lastLoginAt = $lastLoginAt;
        $this->lastLoginIp = $lastLoginIp;

        $this->timezone = $timezone;
        $this->language = $language;

        $this->isActive = $isActive;
        $this->isDeleted = $isDeleted;
        $this->deletedAt = $deletedAt;

        $this->createdAt = $createdAt ?? new DateTimeImmutable();
        $this->updatedAt = $updatedAt ?? new DateTimeImmutable();
        $this->createdBy = $createdBy;
        $this->updatedBy = $updatedBy;

        $this->refreshTokens = $refreshTokens;
        $this->roles = $roles;
        $this->permissions = $permissions;
    }

    public function activate(): void
    {
        $this->isActive = true;
    }

    public function deactivate(): void
    {
        $this->isActive = false;
    }

    public function markDeleted(): void
    {
        $this->isDeleted = true;
    }

    public function addRefreshToken(RefreshToken $token): void
    {
        $this->refreshTokens[] = $token;
    }

    public function verifyEmail(?DateTimeImmutable $at = null): void
    {
        $this->emailVerifiedAt = $at ?? new DateTimeImmutable();
    }

    public function updateLastLogin(?string $ip): void
    {
        $this->lastLoginAt = new DateTimeImmutable();
        $this->lastLoginIp = $ip;
    }

    public function clearRememberToken(): void
    {
        $this->rememberToken = null;
    }
}
