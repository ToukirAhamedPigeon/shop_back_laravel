<?php

namespace Modules\Shared\Infrastructure\Repositories;

use Modules\Shared\Application\Repositories\IPasswordResetRepository;
use Modules\Shared\Domain\Entities\PasswordReset as PasswordResetEntity;
use Modules\Shared\Infrastructure\Models\EloquentPasswordReset;
use Modules\Shared\Infrastructure\Models\EloquentUser;
use DateTimeImmutable;

class EloquentPasswordResetRepository implements IPasswordResetRepository
{
    public function getByToken(string $token, string $tokenType = 'reset'): ?PasswordResetEntity
    {
        $model = EloquentPasswordReset::with('user')
            ->where('token', $token)
            ->where('used', false)
            ->where('token_type', $tokenType)
            ->first();

        return $model ? $this->mapToEntity($model) : null;
    }

    public function getByTokenAsync(string $token, string $tokenType = 'reset'): ?PasswordResetEntity
    {
        // In Laravel, async is handled at the framework level
        // This method is kept for interface compatibility
        return $this->getByToken($token, $tokenType);
    }

    public function getAllByUser(string $userId, string $tokenType): array
    {
        return EloquentPasswordReset::with('user')
            ->where('user_id', $userId)
            ->where('token_type', $tokenType)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($m) => $this->mapToEntity($m))
            ->toArray();
    }

    public function getAllByUserAsync(string $userId, string $tokenType): array
    {
        // In Laravel, async is handled at the framework level
        return $this->getAllByUser($userId, $tokenType);
    }

    public function add(PasswordResetEntity $passwordReset): PasswordResetEntity
    {
        $model = new EloquentPasswordReset();
        $this->mapToModel($passwordReset, $model);
        $model->save();

        return $this->mapToEntity($model->fresh('user'));
    }

    public function addAsync(PasswordResetEntity $passwordReset): PasswordResetEntity
    {
        return $this->add($passwordReset);
    }

    public function update(PasswordResetEntity $passwordReset): PasswordResetEntity
    {
        $model = EloquentPasswordReset::findOrFail($passwordReset->id);
        $this->mapToModel($passwordReset, $model);
        $model->save();

        return $this->mapToEntity($model->fresh('user'));
    }

    public function updateAsync(PasswordResetEntity $passwordReset): PasswordResetEntity
    {
        return $this->update($passwordReset);
    }

    public function saveChanges(): void
    {
        // In Laravel, changes are auto-saved when calling save() on model
        // This method is kept for interface compatibility
        return;
    }

    public function saveChangesAsync(): void
    {
        $this->saveChanges();
    }

    public function markExistingTokensAsUsed(string $userId, string $tokenType): void
    {
        EloquentPasswordReset::where('user_id', $userId)
            ->where('token_type', $tokenType)
            ->where('used', false)
            ->update(['used' => true]);
    }

    public function markExistingTokensAsUsedAsync(string $userId, string $tokenType): void
    {
        $this->markExistingTokensAsUsed($userId, $tokenType);
    }

    public function markUsed(PasswordResetEntity $passwordReset): void
    {
        EloquentPasswordReset::where('id', $passwordReset->id)
            ->update(['used' => true]);
    }

    public function markAllAsUsedForUser(string $userId): void
    {
        EloquentPasswordReset::where('user_id', $userId)
            ->where('used', false)
            ->update(['used' => true]);
    }

    public function deleteExpired(): int
    {
        return EloquentPasswordReset::where('expires_at', '<', now())
            ->delete();
    }

    private function mapToModel(PasswordResetEntity $entity, EloquentPasswordReset $model): void
    {
        $model->token = $entity->token;
        $model->user_id = $entity->userId;
        $model->expires_at = $entity->expiresAt->format('Y-m-d H:i:s');
        $model->used = $entity->used;
        $model->token_type = $entity->tokenType;
        $model->new_password_hash = $entity->newPasswordHash;
    }

    private function mapToEntity(EloquentPasswordReset $model): PasswordResetEntity
    {
        $userEntity = null;
        if ($model->relationLoaded('user') && $model->user) {
            $userEntity = $this->mapUserToEntity($model->user);
        }

        return new PasswordResetEntity(
            id: $model->id,
            token: $model->token,
            userId: $model->user_id,
            expiresAt: new DateTimeImmutable($model->expires_at),
            used: $model->used,
            createdAt: new DateTimeImmutable($model->created_at),
            tokenType: $model->token_type ?? 'reset',
            newPasswordHash: $model->new_password_hash,
            user: $userEntity
        );
    }

    private function mapUserToEntity(EloquentUser $model): \Modules\Shared\Domain\Entities\User
    {
        return new \Modules\Shared\Domain\Entities\User(
            id: $model->id,
            name: $model->name,
            username: $model->username,
            email: $model->email,
            password: $model->password,
            profileImage: $model->profile_image,
            bio: $model->bio,
            dateOfBirth: $model->date_of_birth ? new DateTimeImmutable($model->date_of_birth) : null,
            gender: $model->gender,
            address: $model->address,
            mobileNo: $model->mobile_no,
            emailVerifiedAt: $model->email_verified_at ? new DateTimeImmutable($model->email_verified_at) : null,
            qrCode: $model->qr_code,
            rememberToken: $model->remember_token,
            lastLoginAt: $model->last_login_at ? new DateTimeImmutable($model->last_login_at) : null,
            lastLoginIp: $model->last_login_ip,
            timezone: $model->timezone,
            language: $model->language,
            nid: $model->nid,
            isActive: $model->is_active,
            isDeleted: $model->is_deleted,
            deletedAt: $model->deleted_at ? new DateTimeImmutable($model->deleted_at) : null,
            createdAt: new DateTimeImmutable($model->created_at),
            updatedAt: new DateTimeImmutable($model->updated_at),
            createdBy: $model->created_by,
            updatedBy: $model->updated_by,
            refreshTokens: []
        );
    }
}
