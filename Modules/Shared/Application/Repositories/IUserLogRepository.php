<?php

namespace Modules\Shared\Application\Repositories;

use Modules\Shared\Domain\Entities\UserLog;
use Modules\Shared\Application\Requests\UserLog\UserLogFilterRequest;
use Modules\Shared\Application\Requests\Common\SelectOptionRequest;

interface IUserLogRepository
{
    public function create(UserLog $log): UserLog;
    public function createAsync(UserLog $log): UserLog;

    public function getById(string $id): ?UserLog;
    public function getByIdAsync(string $id): ?UserLog;

    public function getByUserId(string $createdBy): array;
    public function getByUserIdAsync(string $createdBy): array;

    public function getAll(): array;
    public function getAllAsync(): array;

    public function saveChanges(): void;
    public function saveChangesAsync(): void;

    public function getFiltered(UserLogFilterRequest $req): array;
    public function getFilteredAsync(UserLogFilterRequest $req): array;

    public function getDistinctModelNames(SelectOptionRequest $req): array;
    public function getDistinctModelNamesAsync(SelectOptionRequest $req): array;

    public function getDistinctActionTypes(SelectOptionRequest $req): array;
    public function getDistinctActionTypesAsync(SelectOptionRequest $req): array;

    public function getDistinctCreators(SelectOptionRequest $req): array;
    public function getDistinctCreatorsAsync(SelectOptionRequest $req): array;
}
