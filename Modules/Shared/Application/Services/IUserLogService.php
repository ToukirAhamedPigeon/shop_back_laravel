<?php

namespace Modules\Shared\Application\Services;

use Modules\Shared\Application\Requests\UserLog\UserLogFilterRequest;
use Modules\Shared\Application\Requests\Common\SelectOptionRequest;

interface IUserLogService
{
    public function getFiltered(UserLogFilterRequest $request): array;
    public function getById(string $id): ?array;

    // public function getCollections(SelectOptionRequest $request): array;
    // public function getActionTypes(SelectOptionRequest $request): array;
    // public function getCreators(SelectOptionRequest $request): array;
}
