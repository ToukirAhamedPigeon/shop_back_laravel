<?php

namespace Modules\Shared\Infrastructure\Services;

use Modules\Shared\Application\Repositories\IMailRepository;
use Modules\Shared\Application\Services\IMailService;
use Modules\Shared\Domain\Entities\Mail as MailEntity;
use Illuminate\Support\Facades\Mail as LaravelMail;
use Illuminate\Support\Facades\Log;


class MailService implements IMailService
{
    private IMailRepository $mailRepository;

    public function __construct(IMailRepository $mailRepository)
    {
        $this->mailRepository = $mailRepository;
    }

    /**
     * ---------------------------------------------------------------------
     *  SEND EMAIL (Equivalent to .NET SendEmailAsync)
     * ---------------------------------------------------------------------
     */
    public function sendEmail(MailEntity $mail): void
    {
        try {
            // 1) Save email in DB (same as .NET)
            $savedMail = $this->mailRepository->create($mail);

            // 2) Prepare HTML message
            $htmlBody = $mail->body;

            // 3) Send using Laravel Mail
            LaravelMail::send([], [], function ($message) use ($mail, $htmlBody) {

                $message->from($mail->fromMail)
                    ->to($mail->toMail)
                    ->subject($mail->subject)
                    ->html($htmlBody);

                // --- Attachments (same logic as .NET) ---
                if (!empty($mail->attachments)) {
                    foreach ($mail->attachments as $filePath) {
                        if (file_exists($filePath)) {
                            $message->attach($filePath);
                        } else {
                            Log::warning("[MailService] Attachment not found: $filePath");
                        }
                    }
                }
            });

        } catch (\Exception $ex) {
            Log::error("MailService sendEmail Error: " . $ex->getMessage());
            throw new \Exception("Error sending email.", 0, $ex);
        }
    }

    /**
     * ---------------------------------------------------------------------
     *  GET MAIL BY ID
     * ---------------------------------------------------------------------
     */
    public function getMailById(int $id): ?MailEntity
    {
        return $this->mailRepository->findById($id);
    }

    /**
     * ---------------------------------------------------------------------
     *  GET ALL MAILS
     * ---------------------------------------------------------------------
     */
    public function getAllMails(): array
    {
        return $this->mailRepository->findAll();
    }


    /**
     * ---------------------------------------------------------------------
     *  EMAIL TEMPLATE BUILDER (Equivalent to .NET BuildEmailTemplate)
     * ---------------------------------------------------------------------
     */
    public function buildEmailTemplate(string $subject, string $bodyContent): string
    {
        // --- Load company details from ENV ---
        $companyName    = env('COMPANY_NAME', 'My Company');
        $companyAddress = env('COMPANY_ADDRESS', '123, Main Street, City');
        $companyPhone   = env('COMPANY_PHONE', '+123456789');
        $companyEmail   = env('COMPANY_EMAIL', 'info@company.com');

        // --- Same HTML template as .NET ---
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
        <p>Best Regards,<br>{$companyName} Team</p>
    </div>
</div>
</body>
</html>
HTML;
    }
}
