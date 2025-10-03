<?php

namespace Modules\Shared\Application\Repositories;

use Modules\Shared\Domain\Entities\TranslationKey;
use Modules\Shared\Domain\Entities\TranslationValue;

interface ITranslationRepository
{
    /**
     * Get all translations for a given language, optionally filtered by module.
     *
     * @param string $lang
     * @param string|null $module
     * @return TranslationValue[]
     */
    public function getByLang(string $lang, ?string $module = null): array;

    /**
     * Get a TranslationKey by module and key.
     *
     * @param string $module
     * @param string $key
     * @return TranslationKey|null
     */
    public function getKey(string $module, string $key): ?TranslationKey;

    /**
     * Add a new translation or update an existing one.
     *
     * @param string $module
     * @param string $key
     * @param string $lang
     * @param string $value
     * @return void
     */
    public function addOrUpdate(string $module, string $key, string $lang, string $value): void;
}
