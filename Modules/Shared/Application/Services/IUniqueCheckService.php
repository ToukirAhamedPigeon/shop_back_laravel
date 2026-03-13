<?php

namespace Modules\Shared\Application\Services;

use Modules\Shared\Application\Requests\Common\CheckUniqueRequest;

interface IUniqueCheckService
{
    /**
     * Check if a value exists in a specific model field
     */
    public function exists(CheckUniqueRequest $request): bool;

    public function existsAsync(CheckUniqueRequest $request): bool;
}
