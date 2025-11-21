<?php

namespace Modules\Shared\Domain\Entities;

use DateTimeImmutable;

final class Mail
{
    public int $id;
    public string $fromMail;
    public string $toMail;
    public string $subject;
    public string $body;
    public string $moduleName;
    public string $purpose;
    public array $attachments; // stored as JSON
    public ?string $createdBy;
    public DateTimeImmutable $createdAt;

    public ?User $createdByUser = null;

    public function __construct(
        int $id,
        string $fromMail,
        string $toMail,
        string $subject,
        string $body,
        string $moduleName,
        string $purpose,
        array $attachments = [],
        ?string $createdBy = null,
        ?DateTimeImmutable $createdAt = null,
        ?User $createdByUser = null
    ) {
        $this->id = $id;
        $this->fromMail = $fromMail;
        $this->toMail = $toMail;
        $this->subject = $subject;
        $this->body = $body;
        $this->moduleName = $moduleName;
        $this->purpose = $purpose;
        $this->attachments = $attachments;
        $this->createdBy = $createdBy;
        $this->createdAt = $createdAt ?? new DateTimeImmutable();
        $this->createdByUser = $createdByUser;
    }
}
