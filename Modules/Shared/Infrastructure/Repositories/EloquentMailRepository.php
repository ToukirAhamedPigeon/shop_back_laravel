<?php

namespace Modules\Shared\Infrastructure\Repositories;

use Modules\Shared\Application\Repositories\IMailRepository;
use Modules\Shared\Domain\Entities\Mail as MailEntity;
use Modules\Shared\Infrastructure\Models\EloquentMail;
use DateTimeImmutable;

class EloquentMailRepository implements IMailRepository
{
    public function findById(int $id): ?MailEntity
    {
        $model = EloquentMail::find($id);
        return $model ? $this->mapToEntity($model) : null;
    }

    public function findAll(): array
    {
        return EloquentMail::orderByDesc('created_at')
            ->get()
            ->map(fn ($m) => $this->mapToEntity($m))
            ->toArray();
    }

    public function add(MailEntity $mail): MailEntity
    {
        $model = new EloquentMail();
        $this->mapToModel($mail, $model);
        $model->save();

        return $this->mapToEntity($model);
    }

    public function update(MailEntity $mail): MailEntity
    {
        $model = EloquentMail::find($mail->id);

        if (!$model) {
            return $mail;
        }

        $this->mapToModel($mail, $model);
        $model->save();

        return $this->mapToEntity($model);
    }

    public function saveChanges(): void
    {
        // In Laravel, changes are auto-saved when calling save() on model
        // This method is kept for interface compatibility
        return;
    }

    private function mapToModel(MailEntity $mail, EloquentMail $model): void
    {
        $model->from_mail = $mail->fromMail;
        $model->to_mail = $mail->toMail;
        $model->subject = $mail->subject;
        $model->body = $mail->body;
        $model->module_name = $mail->moduleName;
        $model->purpose = $mail->purpose;
        $model->created_by = $mail->createdBy;

        // Use getter method instead of direct property access
        $attachments = $mail->getAttachments(); // Assuming you have a getter method
        if (!empty($attachments)) {
            $model->attachments = json_encode($attachments);
        }
    }

    private function mapToEntity(EloquentMail $model): MailEntity
    {
        $mailEntity = new MailEntity(
            id: $model->id,
            fromMail: $model->from_mail,
            toMail: $model->to_mail,
            subject: $model->subject,
            body: $model->body,
            moduleName: $model->module_name,
            purpose: $model->purpose,
            attachmentsJson: $model->attachments ? json_encode($model->attachments) : null,
            createdBy: $model->created_by,
            createdAt: $model->created_at ? new DateTimeImmutable($model->created_at) : new DateTimeImmutable(),
            createdByUser: $model->relationLoaded('createdByUser') && $model->createdByUser ? null : null
        );

        // If the entity has a setter for attachments, use it
        if ($model->attachments) {
            $mailEntity->setAttachments(json_decode($model->attachments, true));
        }

        return $mailEntity;
    }
}
