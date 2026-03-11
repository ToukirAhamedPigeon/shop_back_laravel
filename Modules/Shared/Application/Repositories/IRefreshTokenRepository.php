<?php

namespace Modules\Shared\Application\Repositories;

use Modules\Shared\Domain\Entities\RefreshToken;

interface IRefreshTokenRepository
{
    public function getByToken(string $token): ?RefreshToken;

    public function getByTokenAsync(string $token): ?RefreshToken;

    public function add(RefreshToken $refreshToken): RefreshToken;

    public function addAsync(RefreshToken $refreshToken): RefreshToken;

    public function revoke(RefreshToken $refreshToken): void;

    public function revokeAsync(RefreshToken $refreshToken): void;

    public function revokeAll(string $userId): void;

    public function revokeAllAsync(string $userId): void;

    public function revokeOther(string $exceptToken, string $userId): void;

    public function revokeOtherAsync(string $exceptToken, string $userId): void;

    public function removeExpired(): void;

    public function removeExpiredAsync(): void;

    public function saveChanges(): void;

    public function saveChangesAsync(): void;
}
