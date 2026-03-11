<?php

namespace Modules\Shared\Domain\Entities;

use DateTimeImmutable;

final class MailVerification
{
    public string $id;
    public string $userId;
    public string $token;
    public DateTimeImmutable $expiresAt;
    public bool $isUsed;
    public DateTimeImmutable $createdAt;
    public ?DateTimeImmutable $usedAt;

    public ?User $user;

    public function __construct(
        string $id,
        string $userId,
        string $token,
        DateTimeImmutable $expiresAt,
        bool $isUsed = false,
        ?DateTimeImmutable $createdAt = null,
        ?DateTimeImmutable $usedAt = null,
        ?User $user = null
    ) {
        $this->id = $id;
        $this->userId = $userId;
        $this->token = $token;
        $this->expiresAt = $expiresAt;
        $this->isUsed = $isUsed;
        $this->createdAt = $createdAt ?? new DateTimeImmutable();
        $this->usedAt = $usedAt;
        $this->user = $user;
    }

    public function markAsUsed(): void
    {
        $this->isUsed = true;
        $this->usedAt = new DateTimeImmutable();
    }

    public function isExpired(): bool
    {
        return $this->expiresAt < new DateTimeImmutable();
    }
}
