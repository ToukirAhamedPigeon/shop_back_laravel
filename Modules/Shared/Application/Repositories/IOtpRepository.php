<?php

namespace Modules\Shared\Application\Repositories;

use Modules\Shared\Domain\Entities\Otp;

interface IOtpRepository
{
    public function findByEmailAndPurpose(string $email, string $purpose): ?Otp;
    public function findByCodeHash(string $email, string $codeHash): ?Otp;
    public function findAllByUserId(string $userId): array;
    public function create(Otp $otp): Otp;
    public function update(Otp $otp): Otp;
    public function markUsed(Otp $otp): void;
    public function incrementAttempts(Otp $otp): void;
}
