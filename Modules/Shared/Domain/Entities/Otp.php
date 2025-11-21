<?php

namespace Modules\Shared\Domain\Entities;

use DateTimeImmutable;

final class Otp
{
    public int $id;
    public string $email;
    public string $codeHash;
    public string $purpose;
    public DateTimeImmutable $expiresAt;
    public bool $used;
    public int $attempts;
    public string $userId; // GUID
    public DateTimeImmutable $createdAt;
    public DateTimeImmutable $updatedAt;

    public ?User $user = null;

    public function __construct(
        int $id,
        string $email,
        string $codeHash,
        string $purpose,
        DateTimeImmutable $expiresAt,
        bool $used,
        int $attempts,
        string $userId,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
        ?User $user = null
    ) {
        $this->id = $id;
        $this->email = $email;
        $this->codeHash = $codeHash;
        $this->purpose = $purpose;
        $this->expiresAt = $expiresAt;
        $this->used = $used;
        $this->attempts = $attempts;
        $this->userId = $userId;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
        $this->user = $user;
    }

    public function markUsed(): void
    {
        $this->used = true;
    }

    public function incrementAttempts(): void
    {
        $this->attempts++;
    }

    public function isExpired(): bool
    {
        return $this->expiresAt < new DateTimeImmutable();
    }
}
