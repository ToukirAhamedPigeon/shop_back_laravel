<?php

namespace Modules\Shared\Infrastructure\Repositories;

use Modules\Shared\Application\Repositories\IPasswordResetRepository;
use Modules\Shared\Domain\Entities\PasswordReset as PasswordResetEntity;
use Modules\Shared\Infrastructure\Models\EloquentPasswordReset;
use DateTimeImmutable;

class EloquentPasswordResetRepository implements IPasswordResetRepository
{
    public function findByToken(string $token): ?PasswordResetEntity
    {
        $model = EloquentPasswordReset::where('token', $token)
            ->where('used', false)
            ->first();

        return $model ? $this->mapToEntity($model) : null;
    }

    public function findAllByUserId(string $userId): array
    {
        return EloquentPasswordReset::where('user_id', $userId)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($m) => $this->mapToEntity($m))
            ->toArray();
    }

    public function create(PasswordResetEntity $passwordReset): PasswordResetEntity
    {
        $model = new EloquentPasswordReset();
        $model->token = $passwordReset->token;
        $model->user_id = $passwordReset->userId;
        $model->expires_at = $passwordReset->expiresAt->format('Y-m-d H:i:s');
        $model->used = $passwordReset->used;
        $model->save();

        return $this->mapToEntity($model);
    }

    public function update(PasswordResetEntity $passwordReset): PasswordResetEntity
    {
        $model = EloquentPasswordReset::findOrFail($passwordReset->id);

        $model->token = $passwordReset->token;
        $model->user_id = $passwordReset->userId;
        $model->expires_at = $passwordReset->expiresAt->format('Y-m-d H:i:s');
        $model->used = $passwordReset->used;

        $model->save();

        return $this->mapToEntity($model);
    }

    public function markUsed(PasswordResetEntity $passwordReset): void
    {
        EloquentPasswordReset::where('id', $passwordReset->id)
            ->update(['used' => true]);
    }

    private function mapToEntity(EloquentPasswordReset $model): PasswordResetEntity
    {
        return new PasswordResetEntity(
            id: $model->id,
            token: $model->token,
            userId: $model->user_id,
            expiresAt: new DateTimeImmutable($model->expires_at),
            used: $model->used,
            createdAt: new DateTimeImmutable($model->created_at)
        );
    }
}
