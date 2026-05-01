<?php

namespace Modules\Shared\Infrastructure\Services;

use Modules\Shared\Application\Repositories\ITranslationRepository;
use Modules\Shared\Application\Services\ITranslationService;
use Modules\Shared\Application\Requests\Translation\TranslationFilterRequest;
use Modules\Shared\Application\Requests\Translation\CreateTranslationRequest;
use Modules\Shared\Application\Requests\Translation\UpdateTranslationRequest;
use Modules\Shared\Application\Resources\Translation\TranslationResource;
use Modules\Shared\Application\Resources\Common\BulkOperationResource;
use Modules\Shared\Infrastructure\Models\EloquentUser;
use Modules\Shared\Infrastructure\Helpers\UserLogHelper;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

class TranslationService implements ITranslationService
{
    protected ITranslationRepository $repo;
    protected UserLogHelper $userLogHelper;
    protected int $cacheTtl = 3600; // seconds

    public function __construct(ITranslationRepository $repo, UserLogHelper $userLogHelper)
    {
        $this->repo = $repo;
        $this->userLogHelper = $userLogHelper;
    }

    private function cacheKey(string $lang, ?string $module): string
    {
        return "translations:{$lang}:" . ($module ?? 'common');
    }

    private function getAllModulesCacheKey(): string
    {
        return "translations:all_modules";
    }

    /**
     * Get translations from cache or DB.
     */
    public function getTranslations(string $lang, ?string $module = null, bool $forceDbFetch = false): array
    {
        $cacheKey = $this->cacheKey($lang, $module);

        if (!$forceDbFetch) {
            $cached = Redis::get($cacheKey);
            if ($cached) {
                return json_decode($cached, true);
            }
        }

        $rows = $this->repo->getByLang($lang, $module);

        $map = [];
        foreach ($rows as $row) {
            $map[$row->key->module . '.' . $row->key->key] = $row->value;
        }

        Redis::setex($cacheKey, $this->cacheTtl, json_encode($map));

        return $map;
    }

    /**
     * Get paginated translations with filtering
     */
    public function getTranslationsPaginated(TranslationFilterRequest $request): object
    {
        $result = $this->repo->getFilteredTranslationsQuery($request);

        // Get user names for created_by and updated_by
        $userIds = collect();
        foreach ($result['items'] as $item) {
            if ($item->created_by) $userIds->push($item->created_by);
            if ($item->updated_by) $userIds->push($item->updated_by);
        }

        $usersMap = EloquentUser::whereIn('id', $userIds->unique())
            ->get()
            ->pluck('name', 'id')
            ->toArray();

        $translations = TranslationResource::collection($result['items'], $usersMap);

        return (object) [
            'translations' => $translations,
            'totalCount' => $result['totalCount'],
            'grandTotalCount' => $result['grandTotalCount'],
            'pageIndex' => $request->page - 1,
            'pageSize' => $request->limit,
        ];
    }

    /**
     * Get translation by ID
     */
    public function getTranslationById(int $id): ?array
    {
        $translation = $this->repo->getTranslationKeyWithValues($id);
        if (!$translation) return null;

        // Get user names
        $createdByName = null;
        $updatedByName = null;

        if ($translation->created_by) {
            $creator = EloquentUser::find($translation->created_by);
            $createdByName = $creator?->name;
        }

        if ($translation->updated_by) {
            $updater = EloquentUser::find($translation->updated_by);
            $updatedByName = $updater?->name;
        }

        return (new TranslationResource($translation, $createdByName, $updatedByName))->toArray(request());
    }

    /**
     * Get translation for editing
     */
    public function getTranslationForEdit(int $id): ?array
    {
        return $this->getTranslationById($id);
    }

    /**
     * Create new translation
     */
    public function createTranslation(CreateTranslationRequest $request, ?string $createdBy): array
    {
        // Validate
        if (empty($request->key)) {
            return ['success' => false, 'message' => 'Key is required'];
        }
        if (empty($request->module)) {
            return ['success' => false, 'message' => 'Module is required'];
        }
        if (empty($request->englishValue)) {
            return ['success' => false, 'message' => 'English value is required'];
        }
        if (empty($request->banglaValue)) {
            return ['success' => false, 'message' => 'Bangla value is required'];
        }

        // Check if translation key already exists
        if ($this->repo->translationKeyExists($request->module, $request->key)) {
            return ['success' => false, 'message' => "Translation key '{$request->key}' already exists in module '{$request->module}'"];
        }

        try {
            // Create translation
            $translationKey = $this->repo->createTranslation(
                $request->key,
                $request->module,
                $request->englishValue,
                $request->banglaValue,
                $createdBy
            );

            // Clear cache
            $this->clearCacheForModule($request->module);

            // Log the action
            $this->userLogHelper->log(
                actionType: 'Create',
                detail: "Translation '{$translationKey->key}' created in module '{$translationKey->module}'",
                changes: json_encode([
                    'before' => null,
                    'after' => [
                        'id' => $translationKey->id,
                        'key' => $translationKey->key,
                        'module' => $translationKey->module,
                        'englishValue' => $request->englishValue,
                        'banglaValue' => $request->banglaValue,
                    ]
                ]),
                modelName: 'Translation',
                modelId: (string) $translationKey->id,
                userId: $createdBy
            );

            return ['success' => true, 'message' => 'Translation created successfully'];
        } catch (\Exception $e) {
            Log::error('Error creating translation: ' . $e->getMessage());
            return ['success' => false, 'message' => "Error creating translation: {$e->getMessage()}"];
        }
    }

    /**
     * Update translation
     */
    public function updateTranslation(int $id, UpdateTranslationRequest $request, ?string $updatedBy, bool $isDeveloper): array
    {
        // Get existing translation
        $existing = $this->repo->getTranslationKeyWithValues($id);
        if (!$existing) {
            return ['success' => false, 'message' => 'Translation not found'];
        }

        $existingEnglish = $existing->values->firstWhere('lang', 'en')?->value ?? '';
        $existingBangla = $existing->values->firstWhere('lang', 'bn')?->value ?? '';
        $existingModule = $existing->module;
        $existingKey = $existing->key;

        // Check if key is being changed and if user is developer
        if (($existingKey != $request->key || $existingModule != $request->module) && !$isDeveloper) {
            return ['success' => false, 'message' => 'Only Developer type users can edit translation keys'];
        }

        // Check uniqueness if key or module changed
        if ($existingKey != $request->key || $existingModule != $request->module) {
            if ($this->repo->translationKeyExists($request->module, $request->key, $id)) {
                return ['success' => false, 'message' => "Translation key '{$request->key}' already exists in module '{$request->module}'"];
            }
        }

        try {
            // Update translation
            $this->repo->updateTranslation(
                $id,
                $request->key,
                $request->module,
                $request->englishValue,
                $request->banglaValue,
                $updatedBy
            );

            // Clear cache for old and new modules
            $this->clearCacheForModule($existingModule);
            if ($existingModule != $request->module) {
                $this->clearCacheForModule($request->module);
            }

            // Log the action
            $this->userLogHelper->log(
                actionType: 'Update',
                detail: "Translation '{$request->key}' updated in module '{$request->module}'",
                changes: json_encode([
                    'before' => [
                        'id' => $id,
                        'key' => $existingKey,
                        'module' => $existingModule,
                        'englishValue' => $existingEnglish,
                        'banglaValue' => $existingBangla,
                    ],
                    'after' => [
                        'id' => $id,
                        'key' => $request->key,
                        'module' => $request->module,
                        'englishValue' => $request->englishValue,
                        'banglaValue' => $request->banglaValue,
                    ]
                ]),
                modelName: 'Translation',
                modelId: (string) $id,
                userId: $updatedBy
            );

            return ['success' => true, 'message' => 'Translation updated successfully'];
        } catch (\Exception $e) {
            Log::error('Error updating translation: ' . $e->getMessage());
            return ['success' => false, 'message' => "Error updating translation: {$e->getMessage()}"];
        }
    }

    /**
     * Delete translation
     */
    public function deleteTranslation(int $id, ?string $deletedBy): array
    {
        // Get existing translation
        $existing = $this->repo->getTranslationKeyWithValues($id);
        if (!$existing) {
            return ['success' => false, 'message' => 'Translation not found'];
        }

        $existingEnglish = $existing->values->firstWhere('lang', 'en')?->value ?? '';
        $existingBangla = $existing->values->firstWhere('lang', 'bn')?->value ?? '';

        try {
            // Delete translation
            $this->repo->deleteTranslation($id);

            // Clear cache for affected module
            $this->clearCacheForModule($existing->module);

            // Log the action
            $this->userLogHelper->log(
                actionType: 'Delete',
                detail: "Translation '{$existing->key}' deleted from module '{$existing->module}'",
                changes: json_encode([
                    'before' => [
                        'id' => $id,
                        'key' => $existing->key,
                        'module' => $existing->module,
                        'englishValue' => $existingEnglish,
                        'banglaValue' => $existingBangla,
                    ],
                    'after' => null
                ]),
                modelName: 'Translation',
                modelId: (string) $id,
                userId: $deletedBy
            );

            return ['success' => true, 'message' => 'Translation deleted successfully'];
        } catch (\Exception $e) {
            Log::error('Error deleting translation: ' . $e->getMessage());
            return ['success' => false, 'message' => "Error deleting translation: {$e->getMessage()}"];
        }
    }

    /**
     * Get modules for options
     */
    public function getModulesForOptions(): array
    {
        $cacheKey = $this->getAllModulesCacheKey();
        $cached = Redis::get($cacheKey);

        if ($cached) {
            return json_decode($cached, true);
        }

        $modules = $this->repo->getDistinctModules();
        $result = array_map(function($module) {
            return ['value' => $module, 'label' => $module];
        }, $modules);

        Redis::setex($cacheKey, 1800, json_encode($result)); // 30 minutes cache

        return $result;
    }

    /**
     * Clear cache for a specific module
     */
    private function clearCacheForModule(string $module): void
    {
        // Clear cache for both languages
        Redis::del($this->cacheKey('en', $module));
        Redis::del($this->cacheKey('bn', $module));

        // Also clear the modules list cache
        Redis::del($this->getAllModulesCacheKey());
    }

    /**
     * Bulk delete translations
     */
    public function bulkDeleteTranslations(array $ids, ?string $deletedBy): BulkOperationResource
    {
        // Get modules for affected translations to clear cache
        $affectedModules = [];
        foreach ($ids as $id) {
            $translation = $this->repo->getTranslationKeyWithValues($id);
            if ($translation) {
                $affectedModules[] = $translation->module;
            }
        }
        $affectedModules = array_unique($affectedModules);

        $result = $this->repo->bulkDeleteTranslations($ids, $deletedBy);

        // Log the bulk operation
        if ($result->successCount > 0) {
            $this->userLogHelper->log(
                actionType: 'BulkDelete',
                detail: "Bulk delete of {$result->successCount} translation(s). Failed: {$result->failedCount}",
                changes: json_encode([
                    'ids' => $ids,
                    'successCount' => $result->successCount,
                    'failedCount' => $result->failedCount,
                    'errors' => $result->errors
                ]),
                modelName: 'Translation',
                modelId: 'bulk',
                userId: $deletedBy
            );
        }

        // Clear cache for affected modules
        foreach ($affectedModules as $module) {
            $this->clearCacheForModule($module);
        }

        return $result;
    }
}
