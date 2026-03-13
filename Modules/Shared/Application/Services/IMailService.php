<?php

namespace Modules\Shared\Application\Services;

use Modules\Shared\Domain\Entities\Mail;

interface IMailService
{
    public function sendEmail(Mail $mail): void;

    public function sendEmailAsync(Mail $mail): void;

    public function getMailById(int $id): ?Mail;

    public function getMailByIdAsync(int $id): ?Mail;

    public function getAllMails(): array;

    public function getAllMailsAsync(): array;

    public function buildEmailTemplate(string $bodyContent, string $subject = 'Notification'): string;

    public function sendVerificationEmail(string $toEmail, string $userId, ?string $verificationToken = null): void;

    public function sendVerificationEmailAsync(string $toEmail, string $userId, ?string $verificationToken = null): void;
}
