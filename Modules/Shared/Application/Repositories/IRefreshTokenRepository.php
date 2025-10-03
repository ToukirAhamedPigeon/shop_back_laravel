<?php

namespace Modules\Shared\Application\Repositories;

use Modules\Shared\Domain\Entities\RefreshToken;

interface IRefreshTokenRepository
{
    public function findByToken(string $token): ?RefreshToken;
    public function revoke(RefreshToken $refreshToken, ?string $updatedBy = null): void;
    public function revokeAll(string $userId): void;
    public function revokeOther(string $exceptToken, string $userId): void;
    public function removeExpired(): void;
    public function findById(string $id): ?RefreshToken;
    public function findValidToken(string $token): ?RefreshToken;
    public function create(RefreshToken $token): RefreshToken;
}
