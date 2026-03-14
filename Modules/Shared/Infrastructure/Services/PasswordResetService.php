<?php

namespace Modules\Shared\Infrastructure\Services;

use Modules\Shared\Application\Repositories\IUserRepository;
use Modules\Shared\Application\Services\IPasswordResetService;
use Modules\Shared\Application\Repositories\IPasswordResetRepository;
use Modules\Shared\Application\Services\IMailService;
use Modules\Shared\Application\Requests\Auth\ResetPasswordRequest;
use Modules\Shared\Domain\Entities\PasswordReset;
use Modules\Shared\Infrastructure\Helpers\UserLogHelper;
use DateTimeImmutable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Exception;
use Illuminate\Support\Facades\Log;

class PasswordResetService implements IPasswordResetService
{
    private IUserRepository $userRepository;
    private IPasswordResetRepository $passwordResetRepository;
    private IMailService $mailService;
    private UserLogHelper $userLogHelper;

    public function __construct(
        IUserRepository $userRepository,
        IPasswordResetRepository $passwordResetRepository,
        IMailService $mailService,
        UserLogHelper $userLogHelper
    ) {
        $this->userRepository = $userRepository;
        $this->passwordResetRepository = $passwordResetRepository;
        $this->mailService = $mailService;
        $this->userLogHelper = $userLogHelper;
    }

    /**
     * Request password reset
     */
    public function requestPasswordReset(string $email): void
    {
        // Use getByEmail or findByEmail - check which one exists in your repository
        $user = $this->userRepository->getByEmail($email); // or getByEmail

        if (!$user) {
            throw new Exception("Email not registered.");
        }

        $token = bin2hex(random_bytes(32));
        $expiresAt = new DateTimeImmutable("+1 hour");

        $resetEntity = new PasswordReset(
            id: 0,
            token: $token,
            userId: $user->id,
            expiresAt: $expiresAt,
            used: false,
            createdAt: new DateTimeImmutable()
        );

        // FIX: Use add() instead of create()
        $savedReset = $this->passwordResetRepository->add($resetEntity);

        $frontendAdminUrl = env('FrontendAdminUrl', 'http://localhost:5173');
        $resetLink = "{$frontendAdminUrl}/reset-password/{$token}";

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
            $bodyContent,
            "Reset your password"
        );

        // FIX: Use attachmentsJson instead of attachments
        $mail = new \Modules\Shared\Domain\Entities\Mail(
            id: 0,
            fromMail: env('MAIL_FROM_ADDRESS', 'noreply@shop.com'),
            toMail: $user->email,
            subject: "Reset your password",
            body: $fullBody,
            moduleName: "Auth",
            purpose: "PasswordReset",
            attachmentsJson: null,
            createdBy: $user->id,
            createdAt: new DateTimeImmutable()
        );

        $this->mailService->sendEmail($mail);

        // USER LOG: Password Reset Requested
        try {
            $this->userLogHelper->log(
                actionType: "Create",
                detail: "User requested password reset.",
                changes: null,
                modelName: "PasswordReset",
                modelId: $savedReset->id,
                userId: $user->id
            );
        } catch (\Exception $ex) {
            Log::error("UserLog Error (RequestPasswordReset): " . $ex->getMessage());
        }
    }

    /**
     * Validate token
     */
    public function validateToken(string $token): bool
    {
        $reset = $this->passwordResetRepository->getByToken($token);
        return $reset && !$reset->used && !$reset->isExpired();
    }

    /**
     * Reset user password
     */
    public function resetPassword(ResetPasswordRequest $request): void
    {
        $token = $request->input('token');
        $password = $request->input('password');

        $reset = $this->passwordResetRepository->getByToken($token);

        if (!$reset || $reset->used || $reset->isExpired()) {
            throw new Exception("Invalid or expired token.");
        }

        $user = $this->userRepository->getById($reset->userId);

        if (!$user) {
            throw new Exception("User not found.");
        }

        // Store old password hash
        $oldPasswordHash = $user->password;

        // Hash new password
        $user->password = Hash::make($password);
        $this->userRepository->update($user);

        // Mark token used
        $reset->markUsed();
        $this->passwordResetRepository->update($reset);

        // USER LOG: Password Reset Completed
        $changes = [
            'before' => ['password' => '[REDACTED]'],
            'after'  => ['password' => '[REDACTED]']
        ];

        try {
            $this->userLogHelper->log(
                actionType: "Update",
                detail: "User successfully reset password.",
                changes: json_encode($changes),
                modelName: "User",
                modelId: $user->id,
                userId: $user->id
            );
        } catch (\Exception $ex) {
            Log::error("UserLog Error (ResetPassword): " . $ex->getMessage());
        }
    }
}
