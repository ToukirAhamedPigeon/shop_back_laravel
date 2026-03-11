<?php

namespace Modules\Shared\Application\Services;

use Modules\Shared\Application\Requests\Common\SelectOptionRequest;

interface IOptionsService
{
    /**
     * Get select options for a given type
     *
     * @param string $type The option type (userlogcollections, userlogactiontypes, userlogcreators, usercreators, userupdaters, userdatetypes, roles, permissions)
     * @param SelectOptionRequest $req The request with search, pagination, and where filters
     * @return array Array of SelectOptionResource items
     */
    public function getOptions(string $type, SelectOptionRequest $req): array;

    /**
     * Async version for interface compatibility
     */
    public function getOptionsAsync(string $type, SelectOptionRequest $req): array;
}
