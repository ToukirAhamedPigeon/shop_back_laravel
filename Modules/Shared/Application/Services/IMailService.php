<?php

namespace Modules\Shared\Application\Services;

use Modules\Shared\Domain\Entities\Mail;

interface IMailService
{
    public function sendEmail(Mail $mail): void;

    public function getMailById(int $id): ?Mail;

    public function getAllMails(): array;

    public function buildEmailTemplate(string $subject, string $body): string;
}
