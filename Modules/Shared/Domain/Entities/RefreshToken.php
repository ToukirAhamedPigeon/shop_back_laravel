<?php

namespace Modules\Shared\Domain\Entities;

use DateTimeImmutable;

final class RefreshToken
{
    public string $id;
    public string $token;
    public DateTimeImmutable $expiresAt;
    public bool $isRevoked;
    public string $userId;
    public ?string $updatedBy;
    public ?DateTimeImmutable $updatedAt;

    public ?User $user = null;

    public function __construct(
        string $id,
        string $token,
        DateTimeImmutable $expiresAt,
        string $userId,
        bool $isRevoked = false,
        ?string $updatedBy = null,
        ?DateTimeImmutable $updatedAt = null,
        ?User $user = null
    ) {
        $this->id = $id;
        $this->token = $token;
        $this->expiresAt = $expiresAt;
        $this->isRevoked = $isRevoked;
        $this->userId = $userId;
        $this->updatedBy = $updatedBy;
        $this->updatedAt = $updatedAt;
        $this->user = $user;
    }

    public function revoke(?string $updatedBy = null): void
    {
        $this->isRevoked = true;
        $this->updatedBy = $updatedBy;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function isExpired(): bool
    {
        return $this->expiresAt <= new DateTimeImmutable();
    }
}
