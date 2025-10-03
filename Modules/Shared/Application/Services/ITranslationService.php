<?php

namespace Modules\Shared\Application\Services;

interface ITranslationService
{
    /**
     * Get flattened map of key -> value for requested lang & optional module.
     *
     * @param string $lang
     * @param string|null $module
     * @param bool $forceDbFetch
     *
     * @return array<string,string>
     */
    public function getTranslations(string $lang, ?string $module = null, bool $forceDbFetch = false): array;
}
