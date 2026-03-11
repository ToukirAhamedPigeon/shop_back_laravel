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
     * Get refresh token by its string value (with AsNoTracking equivalent)
     */
    public function getByToken(string $token): ?RefreshToken
    {
        $model = EloquentRefreshToken::with('user')
            ->where('token', $token)
            ->where('is_revoked', false)
            ->first();

        return $model ? $this->mapModelToEntity($model) : null;
    }

    /**
     * Async version for interface compatibility
     */
    public function getByTokenAsync(string $token): ?RefreshToken
    {
        return $this->getByToken($token);
    }

    /**
     * Add a new refresh token and save changes immediately
     */
    public function add(RefreshToken $refreshToken): RefreshToken
    {
        $model = new EloquentRefreshToken();
        $this->mapToModel($refreshToken, $model);
        $model->save();

        return $this->mapModelToEntity($model->fresh('user'));
    }

    /**
     * Async version for interface compatibility
     */
    public function addAsync(RefreshToken $refreshToken): RefreshToken
    {
        return $this->add($refreshToken);
    }

    /**
     * Revoke a single refresh token
     */
    public function revoke(RefreshToken $refreshToken): void
    {
        $model = EloquentRefreshToken::find($refreshToken->id);
        if ($model) {
            $model->update([
                'is_revoked' => true,
                'updated_by' => $refreshToken->userId,
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Async version for interface compatibility
     */
    public function revokeAsync(RefreshToken $refreshToken): void
    {
        $this->revoke($refreshToken);
    }

    /**
     * Revoke all refresh tokens for a user
     */
    public function revokeAll(string $userId): void
    {
        $tokens = EloquentRefreshToken::where('user_id', $userId)
            ->where('is_revoked', false)
            ->get();

        if ($tokens->isEmpty()) {
            return;
        }

        foreach ($tokens as $token) {
            $token->update([
                'is_revoked' => true,
                'updated_by' => $userId,
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Async version for interface compatibility
     */
    public function revokeAllAsync(string $userId): void
    {
        $this->revokeAll($userId);
    }

    /**
     * Revoke all tokens except the given one
     */
    public function revokeOther(string $exceptToken, string $userId): void
    {
        $tokens = EloquentRefreshToken::where('user_id', $userId)
            ->where('token', '!=', $exceptToken)
            ->where('is_revoked', false)
            ->get();

        if ($tokens->isEmpty()) {
            return;
        }

        foreach ($tokens as $token) {
            $token->update([
                'is_revoked' => true,
                'updated_by' => $userId,
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Async version for interface compatibility
     */
    public function revokeOtherAsync(string $exceptToken, string $userId): void
    {
        $this->revokeOther($exceptToken, $userId);
    }

    /**
     * Remove all expired refresh tokens
     */
    public function removeExpired(): void
    {
        $expiredTokens = EloquentRefreshToken::where('expires_at', '<', now())->get();

        if ($expiredTokens->isEmpty()) {
            return;
        }

        EloquentRefreshToken::where('expires_at', '<', now())->delete();
    }

    /**
     * Async version for interface compatibility
     */
    public function removeExpiredAsync(): void
    {
        $this->removeExpired();
    }

    /**
     * Save changes - in Laravel, saves are auto, kept for interface compatibility
     */
    public function saveChanges(): void
    {
        // In Laravel, changes are auto-saved when calling save() on model
        // This method is kept for interface compatibility
        return;
    }

    /**
     * Async version for interface compatibility
     */
    public function saveChangesAsync(): void
    {
        $this->saveChanges();
    }

    /**
     * Map entity to model
     */
    private function mapToModel(RefreshToken $entity, EloquentRefreshToken $model): void
    {
        $model->id = $entity->id;
        $model->token = $entity->token;
        $model->user_id = $entity->userId;
        $model->expires_at = $entity->expiresAt->format('Y-m-d H:i:s');
        $model->is_revoked = $entity->isRevoked;
        $model->updated_by = $entity->updatedBy;
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
                id: $u->id,
                name: $u->name,
                username: $u->username,
                email: $u->email,
                password: $u->password,
                profileImage: $u->profile_image,
                bio: $u->bio,
                dateOfBirth: $u->date_of_birth ? new DateTimeImmutable($u->date_of_birth) : null,
                gender: $u->gender,
                address: $u->address,
                mobileNo: $u->mobile_no,
                emailVerifiedAt: $u->email_verified_at ? new DateTimeImmutable($u->email_verified_at) : null,
                qrCode: $u->qr_code,
                rememberToken: $u->remember_token,
                lastLoginAt: $u->last_login_at ? new DateTimeImmutable($u->last_login_at) : null,
                lastLoginIp: $u->last_login_ip,
                timezone: $u->timezone,
                language: $u->language,
                nid: $u->nid,
                isActive: $u->is_active,
                isDeleted: $u->is_deleted,
                deletedAt: $u->deleted_at ? new DateTimeImmutable($u->deleted_at) : null,
                createdAt: new DateTimeImmutable($u->created_at),
                updatedAt: new DateTimeImmutable($u->updated_at),
                createdBy: $u->created_by,
                updatedBy: $u->updated_by,
                refreshTokens: []
            );
        }

        return new RefreshToken(
            id: $model->id,
            token: $model->token,
            expiresAt: new DateTimeImmutable($model->expires_at),
            userId: $model->user_id,
            isRevoked: $model->is_revoked,
            updatedBy: $model->updated_by,
            updatedAt: $model->updated_at ? new DateTimeImmutable($model->updated_at) : null,
            user: $userEntity
        );
    }
}
