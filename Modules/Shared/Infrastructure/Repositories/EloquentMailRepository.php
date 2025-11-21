<?php

namespace Modules\Shared\Infrastructure\Repositories;

use Modules\Shared\Application\Repositories\IMailRepository;
use Modules\Shared\Domain\Entities\Mail as MailEntity;
use Modules\Shared\Infrastructure\Models\EloquentMail;

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

    public function create(MailEntity $mail): MailEntity
    {
        $model = new EloquentMail();
        $model->from_mail   = $mail->fromMail;
        $model->to_mail     = $mail->toMail;
        $model->subject     = $mail->subject;
        $model->body        = $mail->body;
        $model->module_name = $mail->moduleName;
        $model->purpose     = $mail->purpose;
        $model->created_by  = $mail->createdBy;

        if (!empty($mail->attachments)) {
            $model->attachments = json_encode($mail->attachments);
        }

        $model->save();

        return $this->mapToEntity($model);
    }

    public function update(MailEntity $mail): MailEntity
    {
        $model = EloquentMail::find($mail->id);

        if (!$model) {
            return $mail; // Return unchanged entity
        }

        $model->from_mail   = $mail->fromMail;
        $model->to_mail     = $mail->toMail;
        $model->subject     = $mail->subject;
        $model->body        = $mail->body;
        $model->module_name = $mail->moduleName;
        $model->purpose     = $mail->purpose;
        $model->created_by  = $mail->createdBy;

        if (!empty($mail->attachments)) {
            $model->attachments = json_encode($mail->attachments);
        }

        $model->save();

        return $this->mapToEntity($model);
    }

    private function mapToEntity(EloquentMail $model): MailEntity
    {
        return new MailEntity(
            id:         $model->id,
            fromMail:   $model->from_mail,
            toMail:     $model->to_mail,
            subject:    $model->subject,
            body:       $model->body,
            moduleName: $model->module_name,
            purpose:    $model->purpose,
            createdBy:  $model->created_by,
            attachments: $model->attachments ? json_decode($model->attachments, true) : [],
            createdAt: new \DateTimeImmutable($model->created_at)
        );
    }
}
