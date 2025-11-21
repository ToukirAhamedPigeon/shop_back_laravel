<?php

namespace Modules\Shared\Application\Services;

use Modules\Shared\Application\Requests\Auth\ResetPasswordRequest;

interface IPasswordResetService
{
    public function requestPasswordReset(string $email): void;

    public function validateToken(string $token): bool;

    public function resetPassword(ResetPasswordRequest $request): void;
}
