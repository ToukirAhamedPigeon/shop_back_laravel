<?php

namespace Modules\Shared\Domain\Entities;

use DateTimeImmutable;

final class User
{
    public string $id;
    public string $name;
    public string $username;
    public string $email;
    public string $password;
    public ?string $mobileNo;
    public bool $isActive;
    public bool $isDeleted;
    public DateTimeImmutable $createdAt;
    public DateTimeImmutable $updatedAt;

    /** @var RefreshToken[] */
    public array $refreshTokens = [];

    /** @var string[] */
    public array $roles = [];

    /** @var string[] */
    public array $permissions = [];

    public ?string $rememberToken;              // 🔹 added
    public ?DateTimeImmutable $emailVerifiedAt; // 🔹 added

    public function __construct(
        string $id,
        string $name,
        string $username,
        string $email,
        string $password,
        ?string $mobileNo = null,
        bool $isActive = true,
        bool $isDeleted = false,
        ?DateTimeImmutable $createdAt = null,
        ?DateTimeImmutable $updatedAt = null,
        array $refreshTokens = [],
        array $roles = [],
        array $permissions = [],
        ?string $rememberToken = null,               // 🔹 added
        ?DateTimeImmutable $emailVerifiedAt = null   // 🔹 added
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->username = $username;
        $this->email = $email;
        $this->password = $password;
        $this->mobileNo = $mobileNo;
        $this->isActive = $isActive;
        $this->isDeleted = $isDeleted;
        $this->createdAt = $createdAt ?? new DateTimeImmutable();
        $this->updatedAt = $updatedAt ?? new DateTimeImmutable();
        $this->refreshTokens = $refreshTokens;
        $this->roles = $roles;
        $this->permissions = $permissions;
        $this->rememberToken = $rememberToken;
        $this->emailVerifiedAt = $emailVerifiedAt;
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

    public function verifyEmail(DateTimeImmutable $at = null): void
    {
        $this->emailVerifiedAt = $at ?? new DateTimeImmutable();
    }

    public function clearRememberToken(): void
    {
        $this->rememberToken = null;
    }
}
