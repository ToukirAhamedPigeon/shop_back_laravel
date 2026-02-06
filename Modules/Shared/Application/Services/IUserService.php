<?php

namespace Modules\Shared\Application\Services;

use Modules\Shared\Application\Requests\Users\UserFilterRequest;

interface IUserService
{
    public function getUsers(UserFilterRequest $request): array;
    public function getById(string $id): ?array;
}
