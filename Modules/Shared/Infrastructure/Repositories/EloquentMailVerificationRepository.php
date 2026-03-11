<?php

namespace Modules\Shared\Infrastructure\Repositories;

use Modules\Shared\Application\Repositories\IMailVerificationRepository;
use Modules\Shared\Domain\Entities\MailVerification as MailVerificationEntity;
use Modules\Shared\Domain\Entities\User as UserEntity;
use Modules\Shared\Infrastructure\Models\EloquentMailVerification;
use Modules\Shared\Infrastructure\Models\EloquentUser;
use DateTimeImmutable;

class EloquentMailVerificationRepository implements IMailVerificationRepository
{
    public function findByToken(string $token): ?MailVerificationEntity
    {
        $model = EloquentMailVerification::with('user')
            ->where('token', $token)
            ->first();

        return $model ? $this->mapToEntity($model) : null;
    }

    public function add(MailVerificationEntity $verification): MailVerificationEntity
    {
        $model = new EloquentMailVerification();
        $model->id = $verification->id;
        $model->user_id = $verification->userId;
        $model->token = $verification->token;
        $model->expires_at = $verification->expiresAt->format('Y-m-d H:i:s');
        $model->is_used = $verification->isUsed;
        $model->created_at = $verification->createdAt->format('Y-m-d H:i:s');
        $model->used_at = $verification->usedAt?->format('Y-m-d H:i:s');
        $model->save();

        return $this->mapToEntity($model->fresh('user'));
    }

    public function update(MailVerificationEntity $verification): MailVerificationEntity
    {
        $model = EloquentMailVerification::findOrFail($verification->id);
        $model->is_used = $verification->isUsed;
        $model->used_at = $verification->usedAt?->format('Y-m-d H:i:s');
        $model->save();

        return $this->mapToEntity($model->fresh('user'));
    }

    public function saveChanges(): void
    {
        // In Laravel, changes are auto-saved when calling save() on model
        // This method is kept for interface compatibility
        return;
    }

    public function getLatestByUserId(string $userId): ?MailVerificationEntity
    {
        // 1️⃣ Try to get latest verification record
        $model = EloquentMailVerification::with('user')
            ->where('user_id', $userId)
            ->orderByDesc('created_at')
            ->first();

        if ($model) {
            return $this->mapToEntity($model);
        }

        // 2️⃣ If not found in MailVerification, check User table
        $userModel = EloquentUser::find($userId);

        if (!$userModel) {
            return null;
        }

        // 3️⃣ Return a MailVerificationEntity with user populated but no verification data
        // This matches the .NET behavior of returning an empty object with User populated
        $userEntity = $this->mapUserToEntity($userModel);

        return new MailVerificationEntity(
            id: '', // Empty string for new/not found
            userId: $userModel->id,
            token: '',
            expiresAt: new DateTimeImmutable('1970-01-01'), // DateTime.MinValue equivalent
            isUsed: false,
            createdAt: new DateTimeImmutable(),
            usedAt: null,
            user: $userEntity
        );
    }

    public function markAsUsed(string $id): void
    {
        EloquentMailVerification::where('id', $id)
            ->update([
                'is_used' => true,
                'used_at' => now()
            ]);
    }

    public function delete(string $id): void
    {
        EloquentMailVerification::where('id', $id)->delete();
    }

    public function deleteExpired(): int
    {
        return EloquentMailVerification::where('expires_at', '<', now())
            ->where('is_used', false)
            ->delete();
    }

    private function mapToEntity(EloquentMailVerification $model): MailVerificationEntity
    {
        $userEntity = null;
        if ($model->relationLoaded('user') && $model->user) {
            $userEntity = $this->mapUserToEntity($model->user);
        }

        return new MailVerificationEntity(
            id: $model->id,
            userId: $model->user_id,
            token: $model->token,
            expiresAt: new DateTimeImmutable($model->expires_at),
            isUsed: $model->is_used,
            createdAt: new DateTimeImmutable($model->created_at),
            usedAt: $model->used_at ? new DateTimeImmutable($model->used_at) : null,
            user: $userEntity
        );
    }

    private function mapUserToEntity(EloquentUser $model): UserEntity
    {
        return new UserEntity(
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
