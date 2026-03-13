<?php

namespace Modules\Shared\Application\Services;

use Modules\Shared\Application\Requests\Common\SelectOptionRequest;

interface IOptionsService
{
    /**
     * Get select options for a given type (collections, actionTypes, creators, etc.)
     *
     * @param string $type The option type
     * @param SelectOptionRequest $req Filter/pagination/search request
     * @return array Array of options with value and label
     */
    public function getOptions(string $type, SelectOptionRequest $req): array;

    public function getOptionsAsync(string $type, SelectOptionRequest $req): array;
}
