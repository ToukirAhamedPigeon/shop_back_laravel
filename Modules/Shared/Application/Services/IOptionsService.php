<?php

namespace Modules\Shared\Application\Services;

use Modules\Shared\Application\Requests\Common\SelectOptionRequest;

interface IOptionsService
{
    /**
     * Generic select options
     * Example types:
     *  - userlogcollections
     *  - userlogactiontypes
     *  - userlogcreators
     */
    public function getOptions(string $type, SelectOptionRequest $request): array;
}
