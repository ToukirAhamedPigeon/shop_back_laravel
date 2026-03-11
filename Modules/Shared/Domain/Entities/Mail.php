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
    public ?string $attachmentsJson;
    public ?string $createdBy;
    public DateTimeImmutable $createdAt;

    public ?User $createdByUser;

    /** @var string[] */
    private array $attachments = [];

    public function __construct(
        int $id,
        string $fromMail,
        string $toMail,
        string $subject,
        string $body,
        string $moduleName,
        string $purpose,
        ?string $attachmentsJson = null,
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
        $this->attachmentsJson = $attachmentsJson;
        $this->createdBy = $createdBy;
        $this->createdAt = $createdAt ?? new DateTimeImmutable();
        $this->createdByUser = $createdByUser;

        if ($attachmentsJson) {
            $this->attachments = json_decode($attachmentsJson, true) ?? [];
        }
    }

    public function getAttachments(): array
    {
        return $this->attachments;
    }

    public function setAttachments(array $attachments): void
    {
        $this->attachments = $attachments;
        $this->attachmentsJson = json_encode($attachments);
    }
}
