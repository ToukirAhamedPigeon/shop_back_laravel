<?php

namespace Modules\Shared\Application\Repositories;

use Modules\Shared\Domain\Entities\MailVerification;

interface IMailVerificationRepository
{
    public function findByToken(string $token): ?MailVerification;

    public function add(MailVerification $verification): MailVerification;

    public function update(MailVerification $verification): MailVerification;

    public function saveChanges(): void;

    public function getLatestByUserId(string $userId): ?MailVerification;

    public function markAsUsed(string $id): void;

    public function delete(string $id): void;

    public function deleteExpired(): int;
}
