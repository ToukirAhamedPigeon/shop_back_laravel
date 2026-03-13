<?php

namespace Modules\Shared\Application\Services;

use Modules\Shared\Domain\Entities\User;

interface IMailVerificationService
{
    /**
     * Send verification email to user
     */
    public function sendVerificationEmail(User $user): void;

    public function sendVerificationEmailAsync(User $user): void;

    /**
     * Verify email token
     *
     * @return array{success: bool, message: string}
     */
    public function verifyToken(string $token): array;

    public function verifyTokenAsync(string $token): array;

    /**
     * Resend verification email
     *
     * @return array{success: bool, message: string}
     */
    public function resendVerification(string $userId): array;

    public function resendVerificationAsync(string $userId): array;
}
