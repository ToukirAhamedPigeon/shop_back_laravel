<?php

namespace Modules\Shared\Application\Repositories;

use Modules\Shared\Domain\Entities\UserTableCombination;

interface IUserTableCombinationRepository
{
    public function findByTableIdAndUserId(string $tableId, string $userId): ?UserTableCombination;
    public function create(UserTableCombination $entity): void;
    public function update(UserTableCombination $entity): void;
}
