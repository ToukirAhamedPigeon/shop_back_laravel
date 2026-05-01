<?php

namespace Modules\Shared\Application\Services;

use Modules\Shared\Application\Requests\Translation\TranslationFilterRequest;
use Modules\Shared\Application\Requests\Translation\CreateTranslationRequest;
use Modules\Shared\Application\Requests\Translation\UpdateTranslationRequest;
use Modules\Shared\Application\Resources\Common\BulkOperationResource;

interface ITranslationService
{
    /**
     * Get translations for a given language and module.
     *
     * @param string $lang
     * @param string|null $module
     * @param bool $forceDbFetch
     * @return array<string, string>
     */
    public function getTranslations(string $lang, ?string $module = null, bool $forceDbFetch = false): array;

    /**
     * Get paginated translations with filtering
     *
     * @param TranslationFilterRequest $request
     * @return object
     */
    public function getTranslationsPaginated(TranslationFilterRequest $request): object;

    /**
     * Get translation by ID
     *
     * @param int $id
     * @return array|null
     */
    public function getTranslationById(int $id): ?array;

    /**
     * Get translation for editing
     *
     * @param int $id
     * @return array|null
     */
    public function getTranslationForEdit(int $id): ?array;

    /**
     * Create new translation
     *
     * @param CreateTranslationRequest $request
     * @param string|null $createdBy
     * @return array{success: bool, message: string}
     */
    public function createTranslation(CreateTranslationRequest $request, ?string $createdBy): array;

    /**
     * Update translation
     *
     * @param int $id
     * @param UpdateTranslationRequest $request
     * @param string|null $updatedBy
     * @param bool $isDeveloper
     * @return array{success: bool, message: string}
     */
    public function updateTranslation(int $id, UpdateTranslationRequest $request, ?string $updatedBy, bool $isDeveloper): array;

    /**
     * Delete translation
     *
     * @param int $id
     * @param string|null $deletedBy
     * @return array{success: bool, message: string}
     */
    public function deleteTranslation(int $id, ?string $deletedBy): array;

    /**
     * Get modules for options
     *
     * @return array{value: string, label: string}[]
     */
    public function getModulesForOptions(): array;

    /**
     * Bulk delete translations
     *
     * @param array<int> $ids
     * @param string|null $deletedBy
     * @return BulkOperationResource
     */
     public function bulkDeleteTranslations(array $ids, ?string $deletedBy): BulkOperationResource;
}
