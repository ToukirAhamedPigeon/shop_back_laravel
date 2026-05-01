<?php

namespace Modules\Shared\Infrastructure\Repositories;

use Modules\Shared\Application\Repositories\ITranslationRepository;
use Modules\Shared\Domain\Entities\TranslationKey as TranslationKeyEntity;
use Modules\Shared\Domain\Entities\TranslationValue as TranslationValueEntity;
use Modules\Shared\Infrastructure\Models\EloquentTranslationKey;
use Modules\Shared\Infrastructure\Models\EloquentTranslationValue;
use Modules\Shared\Infrastructure\Models\EloquentUser;
use Modules\Shared\Application\Requests\Translation\TranslationFilterRequest;
use Modules\Shared\Application\Resources\Common\BulkOperationResource;
use Modules\Shared\Application\Resources\Common\BulkOperationErrorResource;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class EloquentTranslationRepository implements ITranslationRepository
{
    /**
     * Get all translations for a given language, optionally filtered by module.
     */
    public function getByLang(string $lang, ?string $module = null): array
    {
        $query = EloquentTranslationValue::with('key')
            ->where('lang', $lang);

        if ($module !== null) {
            $query->whereHas('key', function ($q) use ($module) {
                $q->where('module', $module);
            });
        }

        $values = $query->get();

        return $values->map(function (EloquentTranslationValue $v) {
            return new TranslationValueEntity(
                $v->id,
                $v->key_id,
                $v->lang,
                $v->value,
                $v->created_at ? Carbon::parse($v->created_at)->toDateTimeImmutable() : null,
                null, // updatedAt - no direct mapping
                null  // key - will be set separately
            );
        })->all();
    }

    /**
     * Get a TranslationKey by module and key.
     */
    public function getKey(string $module, string $key): ?TranslationKeyEntity
    {
        $model = EloquentTranslationKey::with('values')
            ->where('module', $module)
            ->where('key', $key)
            ->first();

        if (!$model) return null;

        $values = $model->values->map(function (EloquentTranslationValue $v) {
            return new TranslationValueEntity(
                $v->id,
                $v->key_id,
                $v->lang,
                $v->value,
                $v->created_at ? Carbon::parse($v->created_at)->toDateTimeImmutable() : null,
                null  // updatedAt
            );
        })->all();

        return new TranslationKeyEntity(
            $model->id,
            $model->key,
            $model->module,
            $model->created_at ? Carbon::parse($model->created_at)->toDateTimeImmutable() : null,
            $model->updated_at ? Carbon::parse($model->updated_at)->toDateTimeImmutable() : null,
            $model->created_by,
            $model->updated_by,
            $values
        );
    }

    /**
     * Add or update a translation.
     */
    public function addOrUpdate(string $module, string $key, string $lang, string $value): void
    {
        $tkey = EloquentTranslationKey::firstOrCreate(
            ['module' => $module, 'key' => $key],
            ['created_at' => now()]
        );

        $tval = EloquentTranslationValue::firstOrNew(
            ['key_id' => $tkey->id, 'lang' => $lang]
        );
        $tval->value = $value;
        $tval->created_at = $tval->exists ? $tval->created_at : now();
        $tval->save();
    }

    /**
     * Get filtered translations query with pagination
     */
    public function getFilteredTranslationsQuery(TranslationFilterRequest $request): array
    {
        $query = EloquentTranslationKey::with('values');

        // Apply search filter
        if ($request->filled('q')) {
            $q = trim($request->q);
            $query->where(function ($qry) use ($q) {
                $qry->where('key', 'like', "%{$q}%")
                    ->orWhere('module', 'like', "%{$q}%")
                    ->orWhereHas('values', function ($subQ) use ($q) {
                        $subQ->where('value', 'like', "%{$q}%");
                    });
            });
        }

        // Apply module filter
        if ($request->filled('modules')) {
            $query->whereIn('module', $request->modules);
        }

        // Apply date range filter
        if ($request->filled('startDate')) {
            $startDate = Carbon::parse($request->startDate)->startOfDay();
            $query->where('created_at', '>=', $startDate);
        }

        if ($request->filled('endDate')) {
            $endDate = Carbon::parse($request->endDate)->endOfDay();
            $query->where('created_at', '<=', $endDate);
        }

        // Get total count
        $totalCount = $query->count();
        $grandTotalCount = EloquentTranslationKey::count();

        // Apply sorting
        $sortColumn = $request->getSortColumn();
        $sortOrder = $request->sortOrder ?? 'desc';
        $query->orderBy($sortColumn, $sortOrder);

        // Apply pagination
        $page = $request->page ?? 1;
        $limit = $request->limit ?? 10;
        $items = $query->skip(($page - 1) * $limit)->take($limit)->get();

        // Add english and bangla values to each item
        foreach ($items as $item) {
            $item->englishValue = $item->values->firstWhere('lang', 'en')?->value ?? '';
            $item->banglaValue = $item->values->firstWhere('lang', 'bn')?->value ?? '';
        }

        return [
            'items' => $items,
            'totalCount' => $totalCount,
            'grandTotalCount' => $grandTotalCount,
        ];
    }

    /**
     * Get translation key with values by ID
     */
    public function getTranslationKeyWithValues(int $id): ?object
    {
        $translation = EloquentTranslationKey::with('values')->find($id);

        if ($translation) {
            // Add english and bangla values as properties
            $translation->englishValue = $translation->values->firstWhere('lang', 'en')?->value ?? '';
            $translation->banglaValue = $translation->values->firstWhere('lang', 'bn')?->value ?? '';
        }

        return $translation;
    }

    /**
     * Check if translation key exists
     */
    public function translationKeyExists(string $module, string $key, ?int $ignoreId = null): bool
    {
        $query = EloquentTranslationKey::where('module', $module)->where('key', $key);
        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }
        return $query->exists();
    }

    /**
     * Create new translation
     */
    public function createTranslation(string $key, string $module, string $englishValue, string $banglaValue, ?string $createdBy): object
    {
        DB::beginTransaction();

        try {
            // Create translation key
            $translationKey = EloquentTranslationKey::create([
                'key' => $key,
                'module' => $module,
                'created_at' => now(),
                'updated_at' => null,
                'created_by' => $createdBy,
                'updated_by' => null,
            ]);

            // Create English value
            EloquentTranslationValue::create([
                'key_id' => $translationKey->id,
                'lang' => 'en',
                'value' => $englishValue,
                'created_at' => now(),
            ]);

            // Create Bangla value
            EloquentTranslationValue::create([
                'key_id' => $translationKey->id,
                'lang' => 'bn',
                'value' => $banglaValue,
                'created_at' => now(),
            ]);

            DB::commit();

            return $translationKey;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update translation
     */
    public function updateTranslation(int $id, string $key, string $module, string $englishValue, string $banglaValue, ?string $updatedBy): void
    {
        DB::beginTransaction();

        try {
            $translationKey = EloquentTranslationKey::with('values')->findOrFail($id);

            // Update key and module
            $translationKey->update([
                'key' => $key,
                'module' => $module,
                'updated_at' => now(),
                'updated_by' => $updatedBy,
            ]);

            // Update or create English value
            $englishValueModel = $translationKey->values->firstWhere('lang', 'en');
            if ($englishValueModel) {
                $englishValueModel->update(['value' => $englishValue]);
            } else {
                EloquentTranslationValue::create([
                    'key_id' => $translationKey->id,
                    'lang' => 'en',
                    'value' => $englishValue,
                    'created_at' => now(),
                ]);
            }

            // Update or create Bangla value
            $banglaValueModel = $translationKey->values->firstWhere('lang', 'bn');
            if ($banglaValueModel) {
                $banglaValueModel->update(['value' => $banglaValue]);
            } else {
                EloquentTranslationValue::create([
                    'key_id' => $translationKey->id,
                    'lang' => 'bn',
                    'value' => $banglaValue,
                    'created_at' => now(),
                ]);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Delete translation
     */
    public function deleteTranslation(int $id): void
    {
        DB::beginTransaction();

        try {
            $translationKey = EloquentTranslationKey::with('values')->findOrFail($id);

            // Delete values first
            foreach ($translationKey->values as $value) {
                $value->delete();
            }

            // Delete key
            $translationKey->delete();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get distinct modules
     */
    public function getDistinctModules(): array
    {
        return EloquentTranslationKey::select('module')
            ->distinct()
            ->orderBy('module')
            ->pluck('module')
            ->toArray();
    }

    /**
     * Bulk delete translations
     */
    public function bulkDeleteTranslations(array $ids, ?string $deletedBy): BulkOperationResource
    {
        $totalCount = count($ids);
        $successCount = 0;
        $failedCount = 0;
        $errors = [];
        $success = true;

        DB::beginTransaction();

        try {
            foreach ($ids as $id) {
                try {
                    $translationKey = EloquentTranslationKey::with('values')
                        ->find($id);

                    if (!$translationKey) {
                        $failedCount++;
                        $errors[] = new BulkOperationErrorResource([
                            'id' => (string) $id,
                            'error' => "Translation with id {$id} not found"
                        ]);
                        $success = false;
                        continue;
                    }

                    // Delete translation values
                    foreach ($translationKey->values as $value) {
                        $value->delete();
                    }

                    // Delete translation key
                    $translationKey->delete();

                    $successCount++;
                } catch (\Exception $ex) {
                    $failedCount++;
                    $errors[] = new BulkOperationErrorResource([
                        'id' => (string) $id,
                        'error' => $ex->getMessage()
                    ]);
                    $success = false;
                }
            }

            DB::commit();

            return new BulkOperationResource([
                'success' => $success,
                'message' => "Processed {$totalCount} translations. Success: {$successCount}, Failed: {$failedCount}",
                'totalCount' => $totalCount,
                'successCount' => $successCount,
                'failedCount' => $failedCount,
                'errors' => $errors,
            ]);
        } catch (\Exception $ex) {
            DB::rollBack();
            return new BulkOperationResource([
                'success' => false,
                'message' => "Bulk operation failed: {$ex->getMessage()}",
                'totalCount' => $totalCount,
                'successCount' => $successCount,
                'failedCount' => $failedCount,
                'errors' => $errors,
            ]);
        }
    }
}
