<?php

namespace Modules\Shared\Domain\Entities;

use DateTimeImmutable;

final class PasswordReset
{
    public int $id;
    public string $token;
    public string $userId;
    public DateTimeImmutable $expiresAt;
    public bool $used;
    public DateTimeImmutable $createdAt;
    public string $tokenType; // "reset" or "change"
    public ?string $newPasswordHash;

    public ?User $user;

    public function __construct(
        int $id,
        string $token,
        string $userId,
        DateTimeImmutable $expiresAt,
        bool $used,
        DateTimeImmutable $createdAt,
        string $tokenType = 'reset',
        ?string $newPasswordHash = null,
        ?User $user = null
    ) {
        $this->id = $id;
        $this->token = $token;
        $this->userId = $userId;
        $this->expiresAt = $expiresAt;
        $this->used = $used;
        $this->createdAt = $createdAt;
        $this->tokenType = $tokenType;
        $this->newPasswordHash = $newPasswordHash;
        $this->user = $user;
    }

    public function markUsed(): void
    {
        $this->used = true;
    }

    public function isExpired(): bool
    {
        return $this->expiresAt < new DateTimeImmutable();
    }

    public function isResetToken(): bool
    {
        return $this->tokenType === 'reset';
    }

    public function isChangeToken(): bool
    {
        return $this->tokenType === 'change';
    }

    public function setNewPasswordHash(string $hash): void
    {
        $this->newPasswordHash = $hash;
    }
}
