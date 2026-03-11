<?php

namespace Modules\Shared\Application\Repositories;

use Modules\Shared\Domain\Entities\Mail;

interface IMailRepository
{
    public function findById(int $id): ?Mail;

    public function findAll(): array;

    public function add(Mail $mail): Mail;

    public function update(Mail $mail): Mail;

    public function saveChanges(): void;
}
