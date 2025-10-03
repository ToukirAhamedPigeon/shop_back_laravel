<?php

namespace Modules\Shared\Application\Repositories;

interface IRepository
{
    public function getAll(): array;
    public function getById(string $id): mixed;
    public function add(object $entity): object;
    public function update(object $entity): void;
    public function delete(object $entity): void;
}
