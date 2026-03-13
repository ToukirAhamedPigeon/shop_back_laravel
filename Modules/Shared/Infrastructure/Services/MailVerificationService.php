<?php

namespace Modules\Shared\Infrastructure\Services;

use Modules\Shared\Application\Services\IMailVerificationService;
use Modules\Shared\Application\Services\IMailService;
use Modules\Shared\Application\Repositories\IMailVerificationRepository;
use Modules\Shared\Domain\Entities\User;
use Modules\Shared\Domain\Entities\MailVerification;
use Modules\Shared\Domain\Entities\Mail;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class MailVerificationService implements IMailVerificationService
{
    private IMailVerificationRepository $repo;
    private IMailService $mailService;

    // Token validity in hours
    private int $tokenExpiryHours = 24;

    public function __construct(
        IMailVerificationRepository $repo,
        IMailService $mailService
    ) {
        $this->repo = $repo;
        $this->mailService = $mailService;
    }

    /**
     * Send verification email to user
     */
    public function sendVerificationEmail(User $user): void
    {
        $frontendAdminUrl = env('FRONTEND_ADMIN_URL', 'http://localhost:3000');

        // Generate unique token (GUID without hyphens)
        $token = Str::uuid()->getHex()->toString();

        $verification = new MailVerification(
            id: (string) Str::uuid(),
            userId: $user->id,
            token: $token,
            expiresAt: Carbon::now()->addHours($this->tokenExpiryHours)->toDateTimeImmutable(),
            isUsed: false,
            createdAt: Carbon::now()->toDateTimeImmutable()
        );

        $this->repo->add($verification);
        $this->repo->saveChanges();

        // Build verification link
        $verificationLink = "{$frontendAdminUrl}/verify-email?token={$token}";

        // Build email body
        $emailBody = "
            <p>Hi {$user->name},</p>
            <p>Please verify your email by clicking the link below:</p>
            <a href='{$verificationLink}' class='button'>Verify Email</a>
            <p>This link will expire in {$this->tokenExpiryHours} hours.</p>
        ";

        $mail = new Mail(
            id: 0,
            fromMail: env('MAIL_FROM_ADDRESS', 'no-reply@yourdomain.com'),
            toMail: $user->email,
            subject: 'Email Verification',
            body: $this->mailService->buildEmailTemplate($emailBody, 'Verify Your Email'),
            moduleName: 'Auth',
            purpose: 'EmailVerification',
            attachmentsJson: null,
            createdBy: $user->id,
            createdAt: Carbon::now()->toDateTimeImmutable()
        );

        $this->mailService->sendEmail($mail);
    }

    public function sendVerificationEmailAsync(User $user): void
    {
        $this->sendVerificationEmail($user);
    }

    /**
     * Verify email token
     *
     * @return array{success: bool, message: string}
     */
    public function verifyToken(string $token): array
    {
        $verification = $this->repo->findByToken($token);

        if (!$verification) {
            return [
                'success' => false,
                'message' => 'Token not found.'
            ];
        }

        if ($verification->isUsed) {
            return [
                'success' => false,
                'message' => 'Token has already been used.'
            ];
        }

        if ($verification->isExpired()) {
            return [
                'success' => false,
                'message' => 'Token has expired.'
            ];
        }

        // Mark token as used
        $verification->markAsUsed();

        // Update user's EmailVerifiedAt
        if ($verification->user) {
            $verification->user->verifyEmail();
        }

        $this->repo->update($verification);
        $this->repo->saveChanges();

        return [
            'success' => true,
            'message' => 'Email verified successfully!'
        ];
    }

    public function verifyTokenAsync(string $token): array
    {
        return $this->verifyToken($token);
    }

    /**
     * Resend verification email
     *
     * @return array{success: bool, message: string}
     */
    public function resendVerification(string $userId): array
    {
        Log::info('Resend verification for user: ' . $userId);

        $existing = $this->repo->getLatestByUserId($userId);

        // Check if previous verification is still valid
        if ($existing && !$existing->isUsed && !$existing->isExpired()) {
            return [
                'success' => false,
                'message' => 'Previous verification email is still valid.'
            ];
        }

        $user = $existing?->user;

        if (!$user) {
            return [
                'success' => false,
                'message' => 'User not found.'
            ];
        }

        if ($user->emailVerifiedAt !== null) {
            return [
                'success' => false,
                'message' => 'Email already verified.'
            ];
        }

        $this->sendVerificationEmail($user);

        return [
            'success' => true,
            'message' => 'Verification email sent successfully.'
        ];
    }

    public function resendVerificationAsync(string $userId): array
    {
        return $this->resendVerification($userId);
    }
}
