<?php

namespace Modules\Shared\Application\Repositories;

use Modules\Shared\Domain\Entities\TranslationKey;
use Modules\Shared\Domain\Entities\TranslationValue;
use Modules\Shared\Application\Requests\Translation\TranslationFilterRequest;
use Illuminate\Database\Eloquent\Collection;

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

    /**
     * Get filtered translations query with pagination
     *
     * @param TranslationFilterRequest $request
     * @return array{items: Collection, totalCount: int, grandTotalCount: int}
     */
    public function getFilteredTranslationsQuery(TranslationFilterRequest $request): array;

    /**
     * Get translation key with values by ID
     *
     * @param int $id
     * @return object|null
     */
    public function getTranslationKeyWithValues(int $id): ?object;

    /**
     * Check if translation key exists
     *
     * @param string $module
     * @param string $key
     * @param int|null $ignoreId
     * @return bool
     */
    public function translationKeyExists(string $module, string $key, ?int $ignoreId = null): bool;

    /**
     * Create new translation
     *
     * @param string $key
     * @param string $module
     * @param string $englishValue
     * @param string $banglaValue
     * @param string|null $createdBy
     * @return object
     */
    public function createTranslation(string $key, string $module, string $englishValue, string $banglaValue, ?string $createdBy): object;

    /**
     * Update translation
     *
     * @param int $id
     * @param string $key
     * @param string $module
     * @param string $englishValue
     * @param string $banglaValue
     * @param string|null $updatedBy
     * @return void
     */
    public function updateTranslation(int $id, string $key, string $module, string $englishValue, string $banglaValue, ?string $updatedBy): void;

    /**
     * Delete translation
     *
     * @param int $id
     * @return void
     */
    public function deleteTranslation(int $id): void;

    /**
     * Get distinct modules
     *
     * @return string[]
     */
    public function getDistinctModules(): array;
}
