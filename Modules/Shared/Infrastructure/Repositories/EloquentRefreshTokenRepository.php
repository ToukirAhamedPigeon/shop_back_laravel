<?php

namespace Modules\Shared\Infrastructure\Repositories;

use Modules\Shared\Application\Repositories\IRefreshTokenRepository;
use Modules\Shared\Infrastructure\Models\EloquentRefreshToken;
use Modules\Shared\Domain\Entities\RefreshToken;
use Modules\Shared\Domain\Entities\User as UserEntity;
use DateTimeImmutable;

class EloquentRefreshTokenRepository implements IRefreshTokenRepository
{
    /**
     * Find refresh token by its string value (regardless of expiration)
     */
    public function findByToken(string $token): ?RefreshToken
    {
        $model = EloquentRefreshToken::with('user')
            ->where('token', $token)
            ->where('is_revoked', false)
            ->first();

        return $model ? $this->mapModelToEntity($model) : null;
    }

    /**
     * Find only valid (not revoked, not expired) refresh token
     */
    public function findValidToken(string $token): ?RefreshToken
    {
        $model = EloquentRefreshToken::with('user')
            ->where('token', $token)
            ->where('is_revoked', false)
            ->where('expires_at', '>', now())
            ->first();

        return $model ? $this->mapModelToEntity($model) : null;
    }

    /**
     * Find refresh token by ID
     */
    public function findById(string $id): ?RefreshToken
    {
        $model = EloquentRefreshToken::with('user')->find($id);
        return $model ? $this->mapModelToEntity($model) : null;
    }

    /**
     * Create a new refresh token record
     */
    public function create(RefreshToken $refreshToken): RefreshToken
    {
        EloquentRefreshToken::create([
            'id' => $refreshToken->id,
            'token' => $refreshToken->token,
            'user_id' => $refreshToken->userId,
            'expires_at' => $refreshToken->expiresAt->format('Y-m-d H:i:s'),
            'is_revoked' => $refreshToken->isRevoked,
        ]);

        return $refreshToken;
    }

    /**
     * Revoke a single refresh token
     */
    public function revoke(RefreshToken $refreshToken, ?string $updatedBy = null): void
    {
        $model = EloquentRefreshToken::find($refreshToken->id);
        if ($model) {
            $model->update([
                'is_revoked' => true,
                'updated_by' => $updatedBy ?? $refreshToken->userId,
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Revoke all refresh tokens for a user
     */
    public function revokeAll(string $userId): void
    {
        EloquentRefreshToken::where('user_id', $userId)
            ->where('is_revoked', false)
            ->update([
                'is_revoked' => true,
                'updated_by' => $userId,
                'updated_at' => now(),
            ]);
    }

    /**
     * Revoke all tokens except the given one
     */
    public function revokeOther(string $exceptToken, string $userId): void
    {
        EloquentRefreshToken::where('user_id', $userId)
            ->where('token', '!=', $exceptToken)
            ->where('is_revoked', false)
            ->update([
                'is_revoked' => true,
                'updated_by' => $userId,
                'updated_at' => now(),
            ]);
    }

    /**
     * Remove all expired refresh tokens
     */
    public function removeExpired(): void
    {
        EloquentRefreshToken::where('expires_at', '<', now())->delete();
    }

    /**
     * Map Eloquent model to Domain entity
     */
    private function mapModelToEntity(EloquentRefreshToken $model): RefreshToken
    {
        $userEntity = null;
        if ($model->relationLoaded('user') && $model->user) {
            $u = $model->user;
            $userEntity = new UserEntity(
                $u->id,
                $u->name,
                $u->username,
                $u->email,
                $u->password,
                $u->mobile_no,
                $u->is_active,
                $u->is_deleted,
                $u->created_at ? new DateTimeImmutable($u->created_at) : null,
                $u->updated_at ? new DateTimeImmutable($u->updated_at) : null,
                [] // optionally pass refresh tokens
            );
        }

        return new RefreshToken(
            $model->id,
            $model->token,
            new DateTimeImmutable($model->expires_at),
            $model->user_id,
            $model->is_revoked,
            $model->updated_by,
            $model->updated_at ? new DateTimeImmutable($model->updated_at) : null,
            $userEntity
        );
    }
}
