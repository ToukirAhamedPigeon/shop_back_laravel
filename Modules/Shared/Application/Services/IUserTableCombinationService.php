<?php

namespace Modules\Shared\Application\Services;

use Modules\Shared\Application\Requests\UserTableCombinationRequest;

interface IUserTableCombinationService
{
    public function get(string $tableId, string $userId): array;
    public function saveOrUpdate(UserTableCombinationRequest $request, string $authUserId): void;
}
