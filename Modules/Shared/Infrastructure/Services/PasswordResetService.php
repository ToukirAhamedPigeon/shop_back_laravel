<?php

namespace Modules\Shared\Infrastructure\Services;

use Modules\Shared\Application\Repositories\IUserRepository;
use Modules\Shared\Application\Services\IPasswordResetService;
use Modules\Shared\Application\Repositories\IPasswordResetRepository;
use Modules\Shared\Application\Services\IMailService;
use Modules\Shared\Application\Requests\Auth\ResetPasswordRequest;
use Modules\Shared\Domain\Entities\PasswordReset;
use DateTimeImmutable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Exception;

class PasswordResetService implements IPasswordResetService
{
    private IUserRepository $userRepository;
    private IPasswordResetRepository $passwordResetRepository;
    private IMailService $mailService;

    public function __construct(
        IUserRepository $userRepository,
        IPasswordResetRepository $passwordResetRepository,
        IMailService $mailService
    ) {
        $this->userRepository = $userRepository;
        $this->passwordResetRepository = $passwordResetRepository;
        $this->mailService = $mailService;
    }

    /**
     * ---------------------------------------------------------
     *  REQUEST PASSWORD RESET  (Equivalent to .NET)
     * ---------------------------------------------------------
     */
    public function requestPasswordReset(string $email): void
    {
        // Find user by email
        $user = $this->userRepository->findByEmail($email);

        if (!$user) {
            throw new Exception("Email not registered.");
        }

        // Generate secure token (same as .NET RandomNumberGenerator 32 bytes)
        $token = bin2hex(random_bytes(32));

        // Create password reset entity
        $expiresAt = new DateTimeImmutable("+1 hour");

        $resetEntity = new PasswordReset(
            id: 0, // auto increment
            token: $token,
            userId: $user->id,
            expiresAt: $expiresAt,
            used: false,
            createdAt: new DateTimeImmutable()
        );

        // Save in DB
        $savedReset = $this->passwordResetRepository->create($resetEntity);

        // Make reset link (Laravel env equivalent)
        $frontendAdminUrl = env('FrontendAdminUrl');
        $resetLink = "{$frontendAdminUrl}/reset-password/{$token}";

        // Email body
        $bodyContent = "
            <h2>Password Reset Request</h2>
            <p>Hello {$user->name},</p>
            <p>Click the button below to reset your password. This link expires in 1 hour.</p>
            <p style='text-align:center;'>
                <a href='{$resetLink}' class='button'>Reset Password</a>
            </p>
            <p>If you did not request this, please ignore this email.</p>
        ";

        $fullBody = $this->mailService->buildEmailTemplate(
            "Reset your password",
            $bodyContent
        );

        // Send email
        $mail = new \Modules\Shared\Domain\Entities\Mail(
            id: 0,
            fromMail: "noreply@shop.com",
            toMail: $user->email,
            subject: "Reset your password",
            body: $fullBody,
            moduleName: "Auth",
            purpose: "PasswordReset",
            createdBy: $user->id,
            attachments: [] // optional attachments like .NET
        );

        $this->mailService->sendEmail($mail);
    }

    /**
     * ---------------------------------------------------------
     *  VALIDATE TOKEN  (Equivalent to .NET ValidateTokenAsync)
     * ---------------------------------------------------------
     */
    public function validateToken(string $token): bool
    {
        $reset = $this->passwordResetRepository->findByToken($token);

        if (!$reset) return false;
        if ($reset->used) return false;
        if ($reset->isExpired()) return false;

        return true;
    }

    /**
     * ---------------------------------------------------------
     *  RESET PASSWORD  (Equivalent to .NET ResetPasswordAsync)
     * ---------------------------------------------------------
     */
    public function resetPassword(ResetPasswordRequest $request): void
    {
        $token = $request->input('token');
        $password = $request->input('password');

        $reset = $this->passwordResetRepository->findByToken($token);

        if (!$reset || $reset->used || $reset->isExpired()) {
            throw new Exception("Invalid or expired token.");
        }

        // Find user
        $user = $this->userRepository->findById($reset->userId);

        if (!$user) {
            throw new Exception("User not found.");
        }

        // Update password (BCrypt in Laravel)
        $user->password = Hash::make($password);
        $this->userRepository->update($user);

        // Mark token used
        $reset->markUsed();
        $this->passwordResetRepository->update($reset);
    }
}
