<?php

namespace Modules\Shared\Application\Repositories;

use Modules\Shared\Domain\Entities\UserLog;
use Modules\Shared\Application\Requests\UserLog\UserLogFilterRequest;
use Modules\Shared\Application\Requests\Common\SelectOptionRequest;

interface IUserLogRepository
{
    public function create(UserLog $log): UserLog;
    public function findById(string $id): ?UserLog;
    public function getFiltered(UserLogFilterRequest $request): array;

    public function getCollections(SelectOptionRequest $request): array;
    public function getActionTypes(SelectOptionRequest $request): array;
    public function getCreators(SelectOptionRequest $request): array;
}
