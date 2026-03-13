<?php

namespace Modules\Shared\Infrastructure\Services;

use Modules\Shared\Application\Services\IChangePasswordService;
use Modules\Shared\Application\Services\IMailService;
use Modules\Shared\Application\Repositories\IUserRepository;
use Modules\Shared\Application\Repositories\IPasswordResetRepository;
use Modules\Shared\Application\Requests\User\ChangePasswordRequest;
use Modules\Shared\Application\Requests\User\VerifyPasswordChangeRequest;
use Modules\Shared\Application\Resources\User\ChangePasswordResource;
use Modules\Shared\Domain\Entities\PasswordReset;
use Modules\Shared\Domain\Entities\Mail;
use Modules\Shared\Infrastructure\Helpers\UserLogHelper;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ChangePasswordService implements IChangePasswordService
{
    private IUserRepository $userRepo;
    private IPasswordResetRepository $resetRepo;
    private IMailService $mailService;
    private UserLogHelper $userLogHelper;

    public function __construct(
        IUserRepository $userRepo,
        IPasswordResetRepository $resetRepo,
        IMailService $mailService,
        UserLogHelper $userLogHelper
    ) {
        $this->userRepo = $userRepo;
        $this->resetRepo = $resetRepo;
        $this->mailService = $mailService;
        $this->userLogHelper = $userLogHelper;
    }

    /**
     * Request password change (Step 1)
     */
    public function requestChangePassword(string $userId, ChangePasswordRequest $request): ChangePasswordResource
    {
        try {
            // 1️⃣ Get user
            $user = $this->userRepo->getById($userId);
            if (!$user) {
                throw new \Exception('User not found');
            }

            // 2️⃣ Verify current password
            if (!Hash::check($request->currentPassword, $user->password)) {
                throw new \Exception('Current password is incorrect');
            }

            // 3️⃣ Check if new password is same as old
            if (Hash::check($request->newPassword, $user->password)) {
                throw new \Exception('New password must be different from current password');
            }

            // 4️⃣ Mark any existing change tokens as used (security)
            $this->resetRepo->markExistingTokensAsUsed($userId, 'change');

            // 5️⃣ Generate secure token (URL-safe base64)
            $token = strtr(base64_encode(random_bytes(32)), '+/', '-_');

            // 6️⃣ Hash the new password for storage (will be used when verified)
            $newPasswordHash = Hash::make($request->newPassword);

            // 7️⃣ Create token record
            $changeToken = new PasswordReset(
                id: 0, // Auto-increment
                token: $token,
                userId: $user->id,
                expiresAt: Carbon::now()->addHour()->toDateTimeImmutable(),
                used: false,
                createdAt: Carbon::now()->toDateTimeImmutable(),
                tokenType: 'change',
                newPasswordHash: $newPasswordHash,
                user: $user
            );

            $this->resetRepo->add($changeToken); // Changed from create() to add()
            $this->resetRepo->saveChanges();

            // 8️⃣ Send verification email
            $this->sendChangePasswordEmail($user, $token);

            return new ChangePasswordResource(
                message: 'Verification email sent successfully',
                requiresVerification: true
            );

        } catch (\Exception $ex) {
            Log::error('Error in requestChangePassword for user ' . $userId . ': ' . $ex->getMessage());
            throw $ex;
        }
    }

    public function requestChangePasswordAsync(string $userId, ChangePasswordRequest $request): ChangePasswordResource
    {
        return $this->requestChangePassword($userId, $request);
    }

    /**
     * Validate change token
     */
    public function validateChangeToken(string $token): bool
    {
        $reset = $this->resetRepo->getByToken($token, 'change'); // Changed from findByTokenAndType() to getByToken()
        return $reset !== null && !$reset->used && !$reset->isExpired();
    }

    public function validateChangeTokenAsync(string $token): bool
    {
        return $this->validateChangeToken($token);
    }

    /**
     * Complete password change (Step 2)
     */
    public function completeChangePassword(VerifyPasswordChangeRequest $request): void
    {
        // 1️⃣ Get and validate token
        $reset = $this->resetRepo->getByToken($request->token, 'change'); // Changed from findByTokenAndType() to getByToken()

        if (!$reset || $reset->used || $reset->isExpired()) {
            throw new \Exception('Invalid or expired verification token');
        }

        if (empty($reset->newPasswordHash)) {
            throw new \Exception('Token data is corrupted');
        }

        // 2️⃣ Get user
        $user = $this->userRepo->getById($reset->userId);
        if (!$user) {
            throw new \Exception('User not found');
        }

        // 3️⃣ Store old password hash for logging
        $oldPasswordHash = $user->password;

        // 4️⃣ Update password with the pre-hashed password from token
        $user->password = $reset->newPasswordHash;
        $user->updatedAt = Carbon::now()->toDateTimeImmutable();

        // 5️⃣ Mark token as used
        $reset->markUsed();
        $this->resetRepo->markUsed($reset); // Add this line to persist the change

        // 6️⃣ Save changes
        $this->userRepo->update($user);
        $this->resetRepo->update($reset);
        $this->userRepo->saveChanges();
        $this->resetRepo->saveChanges();

        // 7️⃣ Log the change
        $this->logPasswordChange($user, $oldPasswordHash);

        // 8️⃣ Send confirmation email
        $this->sendConfirmationEmail($user);
    }

    public function completeChangePasswordAsync(VerifyPasswordChangeRequest $request): void
    {
        $this->completeChangePassword($request);
    }

    /**
     * Send password change verification email
     */
    private function sendChangePasswordEmail($user, string $token): void
    {
        $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');
        $verifyLink = "{$frontendUrl}/verify-password-change/{$token}";

        $bodyContent = "
            <h2>Password Change Request</h2>
            <p>Hello {$user->name},</p>
            <p>We received a request to change your password.</p>
            <p>Click the button below to confirm this change:</p>
            <p style='text-align:center;'>
                <a href='{$verifyLink}' class='button'>Confirm Password Change</a>
            </p>
            <p><strong>This link expires in 1 hour.</strong></p>
            <p>If you did not request this, please ignore this email or contact support immediately.</p>
            <p>For security reasons, your password will not be changed until you confirm.</p>
        ";

        $fullBody = $this->mailService->buildEmailTemplate($bodyContent, 'Confirm Password Change');

        // ✅ FIX: Use attachmentsJson parameter instead of attachments
        $mail = new Mail(
            id: 0,
            fromMail: env('MAIL_FROM_ADDRESS', 'noreply@shop.com'),
            toMail: $user->email,
            subject: 'Confirm Your Password Change',
            body: $fullBody,
            moduleName: 'Auth',
            purpose: 'PasswordChange',
            attachmentsJson: null, // Changed from 'attachments' to 'attachmentsJson'
            createdBy: $user->id,
            createdAt: Carbon::now()->toDateTimeImmutable()
        );

        $this->mailService->sendEmail($mail);
    }

    /**
     * Send password change confirmation email
     */
    private function sendConfirmationEmail($user): void
    {
        $bodyContent = "
            <h2>Password Changed Successfully</h2>
            <p>Hello {$user->name},</p>
            <p>Your password has been changed successfully.</p>
            <p>If you didn't make this change, please contact support immediately.</p>
        ";

        $fullBody = $this->mailService->buildEmailTemplate($bodyContent, 'Password Changed');

        // ✅ FIX: Use attachmentsJson parameter instead of attachments
        $mail = new Mail(
            id: 0,
            fromMail: env('MAIL_FROM_ADDRESS', 'noreply@shop.com'),
            toMail: $user->email,
            subject: 'Your Password Has Been Changed',
            body: $fullBody,
            moduleName: 'Auth',
            purpose: 'PasswordChangeConfirmation',
            attachmentsJson: null, // Changed from 'attachments' to 'attachmentsJson'
            createdBy: $user->id,
            createdAt: Carbon::now()->toDateTimeImmutable()
        );

        $this->mailService->sendEmail($mail);
    }

    /**
     * Log password change
     */
    private function logPasswordChange($user, string $oldPasswordHash): void
    {
        try {
            $changeObject = [
                'before' => ['Password' => '[REDACTED]'],
                'after' => ['Password' => '[REDACTED]'],
                'action' => 'Password changed via verification'
            ];

            $changesJson = json_encode($changeObject);

            $this->userLogHelper->log(
                actionType: 'Update',
                detail: 'User changed password via email verification',
                changes: $changesJson,
                modelName: 'User',
                modelId: $user->id,
                userId: $user->id
            );
        } catch (\Exception $ex) {
            Log::error('Failed to log password change for user ' . $user->id . ': ' . $ex->getMessage());
        }
    }
}
