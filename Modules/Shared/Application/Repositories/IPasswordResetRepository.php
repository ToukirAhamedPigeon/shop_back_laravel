<?php

namespace Modules\Shared\Application\Repositories;

use Modules\Shared\Domain\Entities\PasswordReset;

interface IPasswordResetRepository
{
    public function findByToken(string $token): ?PasswordReset;
    public function findAllByUserId(string $userId): array;
    public function create(PasswordReset $passwordReset): PasswordReset;
    public function markUsed(PasswordReset $passwordReset): void;
}
