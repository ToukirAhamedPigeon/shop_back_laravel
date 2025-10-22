<?php

namespace Modules\Shared\Infrastructure\Repositories;

use Modules\Shared\Application\Repositories\IOtpRepository;
use Modules\Shared\Domain\Entities\Otp as OtpEntity;
use Modules\Shared\Infrastructure\Models\EloquentOtp;
use DateTimeImmutable;

class EloquentOtpRepository implements IOtpRepository
{
    public function findByEmailAndPurpose(string $email, string $purpose): ?OtpEntity
    {
        $model = EloquentOtp::where('email', $email)
            ->where('purpose', $purpose)
            ->where('used', false)
            ->orderByDesc('created_at')
            ->first();

        return $model ? $this->mapToEntity($model) : null;
    }

    public function findByCodeHash(string $email, string $codeHash): ?OtpEntity
    {
        $model = EloquentOtp::where('email', $email)
            ->where('code_hash', $codeHash)
            ->where('used', false)
            ->first();

        return $model ? $this->mapToEntity($model) : null;
    }

    public function findAllByUserId(string $userId): array
    {
        return EloquentOtp::where('user_id', $userId)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($m) => $this->mapToEntity($m))
            ->toArray();
    }

    public function create(OtpEntity $otp): OtpEntity
    {
        $model = new EloquentOtp();
        $model->email = $otp->email;
        $model->code_hash = $otp->codeHash;
        $model->purpose = $otp->purpose;
        $model->expires_at = $otp->expiresAt->format('Y-m-d H:i:s');
        $model->used = $otp->used;
        $model->attempts = $otp->attempts;
        $model->user_id = $otp->userId;
        $model->save();

        return $this->mapToEntity($model);
    }

    public function update(OtpEntity $otp): OtpEntity
    {
        $model = EloquentOtp::findOrFail($otp->id);
        $model->used = $otp->used;
        $model->attempts = $otp->attempts;
        $model->updated_at = now();
        $model->save();

        return $this->mapToEntity($model);
    }

    public function markUsed(OtpEntity $otp): void
    {
        EloquentOtp::where('id', $otp->id)->update(['used' => true, 'updated_at' => now()]);
    }

    public function incrementAttempts(OtpEntity $otp): void
    {
        EloquentOtp::where('id', $otp->id)->increment('attempts');
    }

    private function mapToEntity(EloquentOtp $model): OtpEntity
    {
        return new OtpEntity(
            $model->id,
            $model->email,
            $model->code_hash,
            $model->purpose,
            new DateTimeImmutable($model->expires_at),
            $model->used,
            $model->attempts,
            $model->user_id,
            new DateTimeImmutable($model->created_at),
            new DateTimeImmutable($model->updated_at)
        );
    }
}
