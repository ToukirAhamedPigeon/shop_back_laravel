<?php

namespace Modules\Shared\Infrastructure\Services;

use Modules\Shared\Application\Services\IMailService;
use Modules\Shared\Application\Repositories\IMailRepository;
use Modules\Shared\Application\Repositories\IMailVerificationRepository;
use Modules\Shared\Domain\Entities\Mail;
use Modules\Shared\Domain\Entities\MailVerification;
use Illuminate\Support\Facades\Mail as LaravelMail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

class MailService implements IMailService
{
    private IMailRepository $mailRepository;
    private IMailVerificationRepository $mailVerificationRepository;

    public function __construct(
        IMailRepository $mailRepository,
        IMailVerificationRepository $mailVerificationRepository
    ) {
        $this->mailRepository = $mailRepository;
        $this->mailVerificationRepository = $mailVerificationRepository;
    }

    /**
     * Send email and save to database
     */
    public function sendEmail(Mail $mail): void
    {
        try {
            // 1) Save mail in DB
            $savedMail = $this->mailRepository->add($mail);
            $this->mailRepository->saveChanges();

            // 2) Get SMTP settings from env
            $smtpHost = env('MAIL_HOST', 'smtp.mailtrap.io');
            $smtpPort = (int) env('MAIL_PORT', 2525);
            $smtpUser = env('MAIL_USERNAME');
            $smtpPass = env('MAIL_PASSWORD');

            // 3) Configure mailer dynamically
            config([
                'mail.mailers.smtp' => [
                    'transport' => 'smtp',
                    'host' => $smtpHost,
                    'port' => $smtpPort,
                    'username' => $smtpUser,
                    'password' => $smtpPass,
                    'encryption' => env('MAIL_ENCRYPTION', 'tls'),
                ]
            ]);

            // 4) Send using Laravel Mail
            LaravelMail::mailer('smtp')->send([], [], function ($message) use ($mail) {
                $message->from($mail->fromMail)
                    ->to($mail->toMail)
                    ->subject($mail->subject)
                    ->html($mail->body);

                // ✅ FIX: Use getter method to access private attachments property
                $attachments = $mail->getAttachments();

                // Add attachments
                if (!empty($attachments)) {
                    foreach ($attachments as $filePath) {
                        if (file_exists($filePath)) {
                            $message->attach($filePath);
                        } else {
                            Log::warning("[MailService] Attachment not found: {$filePath}");
                        }
                    }
                }
            });

        } catch (\Exception $ex) {
            Log::error('Error in sendEmail: ' . $ex->getMessage());
            throw new \Exception('Error sending email.', 0, $ex);
        }
    }

    public function sendEmailAsync(Mail $mail): void
    {
        $this->sendEmail($mail);
    }

    /**
     * Get mail by ID
     */
    public function getMailById(int $id): ?Mail
    {
        return $this->mailRepository->findById($id);
    }

    public function getMailByIdAsync(int $id): ?Mail
    {
        return $this->getMailById($id);
    }

    /**
     * Get all mails
     */
    public function getAllMails(): array
    {
        return $this->mailRepository->findAll();
    }

    public function getAllMailsAsync(): array
    {
        return $this->getAllMails();
    }

    /**
     * Send verification email
     */
    public function sendVerificationEmail(string $toEmail, string $userId, ?string $verificationToken = null): void
    {
        // Generate token if not provided
        if (empty($verificationToken)) {
            $verificationToken = (string) Str::uuid();
        }

        // Save token in verification table
        $mailVerification = new MailVerification(
            id: (string) Str::uuid(),
            userId: $userId,
            token: $verificationToken,
            expiresAt: Carbon::now()->addHours(24)->toDateTimeImmutable(),
            isUsed: false,
            createdAt: Carbon::now()->toDateTimeImmutable()
        );

        $this->mailVerificationRepository->add($mailVerification);
        $this->mailVerificationRepository->saveChanges();

        // Build verification link
        $baseUrl = env('APP_URL', 'http://localhost:5000');
        $verifyLink = "{$baseUrl}/verify-email?token={$verificationToken}";

        // Email body
        $bodyContent = "
            <p>Hi,</p>
            <p>Thank you for registering. Please verify your email by clicking the button below:</p>
            <a href='{$verifyLink}' class='button'>Verify Email</a>
            <p>If you did not request this, please ignore this email.</p>
        ";

        $htmlBody = $this->buildEmailTemplate($bodyContent, 'Verify Your Email');

        // ✅ FIX: Pass attachments as empty array (matching constructor signature)
        $mail = new Mail(
            id: 0,
            fromMail: env('MAIL_FROM_ADDRESS', 'no-reply@company.com'),
            toMail: $toEmail,
            subject: 'Verify Your Email',
            body: $htmlBody,
            moduleName: 'Auth',
            purpose: 'EmailVerification',
            attachmentsJson: null, // or pass empty array if constructor expects array
            createdBy: $userId,
            createdAt: Carbon::now()->toDateTimeImmutable()
        );

        // Alternative if your Mail constructor expects array for attachments:
        // $mail = new Mail(
        //     id: 0,
        //     fromMail: env('MAIL_FROM_ADDRESS', 'no-reply@company.com'),
        //     toMail: $toEmail,
        //     subject: 'Verify Your Email',
        //     body: $htmlBody,
        //     moduleName: 'Auth',
        //     purpose: 'EmailVerification',
        //     attachments: [], // If constructor accepts array
        //     createdBy: $userId,
        //     createdAt: Carbon::now()->toDateTimeImmutable()
        // );

        $this->sendEmail($mail);
    }

    public function sendVerificationEmailAsync(string $toEmail, string $userId, ?string $verificationToken = null): void
    {
        $this->sendVerificationEmail($toEmail, $userId, $verificationToken);
    }

    /**
     * Build email template
     */
    public function buildEmailTemplate(string $bodyContent, string $subject = 'Notification'): string
    {
        $companyName = env('COMPANY_NAME', 'My Company');
        $companyAddress = env('COMPANY_ADDRESS', '123, Main Street, City');
        $companyPhone = env('COMPANY_PHONE', '+123456789');
        $companyEmail = env('COMPANY_EMAIL', 'info@company.com');

        return <<<HTML
<!DOCTYPE html>
<html lang='en'>
<head>
<meta charset='UTF-8'>
<meta name='viewport' content='width=device-width, initial-scale=1.0'>
<title>{$subject}</title>
<style>
    body {
        font-family: 'Arial', sans-serif;
        background-color: #f5f7fa;
        color: #333;
        margin: 0;
        padding: 0;
    }
    .container {
        max-width: 600px;
        margin: 40px auto;
        background: #ffffff;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        border-top: 6px solid #4f46e5;
    }
    .header {
        background-color: #4f46e5;
        color: #fff;
        padding: 20px;
        text-align: center;
        font-size: 28px;
        font-weight: bold;
    }
    .body {
        padding: 30px 20px;
        font-size: 16px;
        line-height: 1.6;
        color: #333;
    }
    .body a {
        color: #4f46e5;
        text-decoration: none;
    }
    .footer {
        background-color: #f1f5f9;
        padding: 20px;
        text-align: center;
        font-size: 14px;
        color: #555;
    }
    .footer a {
        color: #4f46e5;
        text-decoration: none;
    }
    .button {
        display: inline-block;
        padding: 12px 25px;
        margin: 15px 0;
        background-color: #4f46e5;
        color: #fff !important;
        font-weight: bold;
        border-radius: 6px;
        text-decoration: none;
    }
</style>
</head>
<body>
<div class='container'>
    <div class='header'>{$companyName}</div>

    <div class='body'>
        {$bodyContent}
    </div>

    <div class='footer'>
        <p>{$companyAddress}</p>
        <p>Phone: {$companyPhone}  |  Email: <a href='mailto:{$companyEmail}'>{$companyEmail}</a></p>
        <p>Best Regards,<br/>{$companyName} Team</p>
    </div>
</div>
</body>
</html>
HTML;
    }
}
