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
    public ?User $user;

    public function __construct(
        int $id,
        string $token,
        string $userId,
        DateTimeImmutable $expiresAt,
        bool $used,
        DateTimeImmutable $createdAt,
        ?User $user = null
    ) {
        $this->id = $id;
        $this->token = $token;
        $this->userId = $userId;
        $this->expiresAt = $expiresAt;
        $this->used = $used;
        $this->createdAt = $createdAt;
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
}
