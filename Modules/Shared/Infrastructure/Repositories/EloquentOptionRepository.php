<?php

namespace Modules\Shared\Infrastructure\Repositories;

use Modules\Shared\Application\Repositories\IOptionRepository;
use Modules\Shared\Domain\Entities\Option as OptionEntity;
use Modules\Shared\Infrastructure\Models\EloquentOption;
use Modules\Shared\Application\Resources\Option\OptionResource;
use Modules\Shared\Application\Requests\Option\OptionFilterRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use DateTimeImmutable;

class EloquentOptionRepository implements IOptionRepository
{
    public function getFilteredOptions(OptionFilterRequest $request): array
    {
        $isDeletedStr = $request->input('isDeletedStr', 'false');
        $isActiveStr = $request->input('isActiveStr', 'all');
        $parentId = $request->input('parentId');
        $createdFrom = $request->input('createdFrom');
        $createdTo = $request->input('createdTo');
        $q = $request->input('q', '');
        $page = (int) $request->input('page', 1);
        $limit = (int) $request->input('limit', 10);
        $sortBy = $request->input('sortBy', 'createdAt');
        $sortOrder = $request->input('sortOrder', 'desc');

        $query = EloquentOption::query()->with('parent');

        // Handle deleted filter
        if ($isDeletedStr === 'true') {
            $query->onlyTrashed();
        } elseif ($isDeletedStr === 'false') {
            $query->whereNull('deleted_at');
        }

        // Handle active filter
        if ($isActiveStr === 'true') {
            $query->where('is_active', true);
        } elseif ($isActiveStr === 'false') {
            $query->where('is_active', false);
        }

        // Handle parent filter
        if ($parentId === 'null') {
            $query->whereNull('parent_id');
        } elseif (!empty($parentId) && $parentId !== 'all') {
            $query->where('parent_id', $parentId);
        }

        // Handle date range filter
        if (!empty($createdFrom)) {
            $query->whereDate('created_at', '>=', $createdFrom);
        }
        if (!empty($createdTo)) {
            $query->whereDate('created_at', '<=', $createdTo);
        }

        // Handle search
        if (!empty($q)) {
            $query->where('name', 'like', "%{$q}%");
        }

        // Get total count
        $totalCount = $query->count();

        // Get grand total count (all records including deleted)
        $grandTotalCount = EloquentOption::withTrashed()->count();

        // Handle sorting
        $sortColumn = match ($sortBy) {
            'name' => 'name',
            'haschild' => 'has_child',
            'isactive' => 'is_active',
            'createdat' => 'created_at',
            default => 'created_at',
        };
        $query->orderBy($sortColumn, $sortOrder);

        // Pagination
        $options = $query->skip(($page - 1) * $limit)
            ->take($limit)
            ->get();

        // Get user names for audit fields
        $userIds = $options->flatMap(function($option) {
            return [$option->created_by, $option->updated_by, $option->deleted_by];
        })->filter()->unique()->toArray();

        $users = [];
        if (!empty($userIds)) {
            $users = DB::table('users')
                ->whereIn('id', $userIds)
                ->pluck('name', 'id')
                ->toArray();
        }

        // Build parent names map - keyed by child option ID
        $parentNames = [];
        foreach ($options as $option) {
            if ($option->parent_id && $option->parent) {
                $parentNames[$option->id] = $option->parent->name;
            } else {
                $parentNames[$option->id] = null;
            }
        }

        // Map to entities
        $result = [];
        foreach ($options as $option) {
            $entity = $this->mapToEntity($option);
            $result[] = $entity;
        }

        return [
            'options' => OptionResource::collection($result, $parentNames, $users),
            'totalCount' => $totalCount,
            'grandTotalCount' => $grandTotalCount,
            'pageIndex' => $page - 1,
            'pageSize' => $limit,
        ];
    }

    public function getOptionById(string $id): ?OptionEntity
    {
        $model = EloquentOption::withTrashed()->with('parent')->find($id);
        return $model ? $this->mapToEntity($model) : null;
    }

    public function getOptionByIdIncludingDeleted(string $id): ?OptionEntity
    {
        $model = EloquentOption::withTrashed()->with('parent')->find($id);
        return $model ? $this->mapToEntity($model) : null;
    }

    public function optionExists(string $name, ?string $parentId, ?string $ignoreId = null): bool
    {
        $query = EloquentOption::where('name', $name)
            ->where('parent_id', $parentId)
            ->whereNull('deleted_at');

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->exists();
    }

    public function createOption(OptionEntity $option): OptionEntity
    {
        $model = new EloquentOption();
        $model->id = $option->id;
        $model->name = $option->name;
        $model->parent_id = $option->parentId;
        $model->has_child = $option->hasChild;
        $model->is_active = $option->isActive;
        $model->is_deleted = $option->isDeleted;
        $model->created_at = $option->createdAt->format('Y-m-d H:i:s');
        $model->updated_at = $option->updatedAt->format('Y-m-d H:i:s');
        $model->created_by = $option->createdBy;
        $model->updated_by = $option->updatedBy;
        $model->save();

        return $this->mapToEntity($model);
    }

    public function updateOption(OptionEntity $option): OptionEntity
    {
        $model = EloquentOption::findOrFail($option->id);
        $model->name = $option->name;
        $model->parent_id = $option->parentId;
        $model->has_child = $option->hasChild;
        $model->is_active = $option->isActive;
        $model->updated_at = $option->updatedAt->format('Y-m-d H:i:s');
        $model->updated_by = $option->updatedBy;
        $model->save();

        return $this->mapToEntity($model);
    }

    public function deleteOption(string $id, bool $permanent = false, ?string $deletedBy = null): void
    {
        $model = EloquentOption::find($id);
        if (!$model) return;

        if ($permanent) {
            // Update children to remove parent reference
            EloquentOption::where('parent_id', $id)->update([
                'parent_id' => null,
                'has_child' => false,
                'updated_at' => now(),
                'updated_by' => $deletedBy,
            ]);
            $model->forceDelete();
        } else {
            $model->deleted_by = $deletedBy;
            $model->save();
            $model->delete(); // Soft delete
        }
    }

    public function restoreOption(string $id, ?string $restoredBy = null): void
    {
        $model = EloquentOption::withTrashed()->find($id);
        if ($model) {
            $model->restore();
            $model->deleted_by = null;
            $model->updated_by = $restoredBy;
            $model->save();
        }
    }

    public function optionHasChildren(string $optionId): bool
    {
        return EloquentOption::where('parent_id', $optionId)
            ->whereNull('deleted_at')
            ->exists();
    }

    public function getChildrenCount(string $optionId): int
    {
        return EloquentOption::where('parent_id', $optionId)
            ->whereNull('deleted_at')
            ->count();
    }

    public function getParentOptions(bool $onlyWithChildren = true): array
    {
        $query = EloquentOption::whereNull('deleted_at')
            ->where('is_active', true);

        if ($onlyWithChildren) {
            $query->where('has_child', true);
        }

        return $query->orderBy('name')->get()->toArray();
    }

    public function saveChanges(): void
    {
        // Not needed for Eloquent as changes are auto-saved
    }

    private function mapToEntity(EloquentOption $model): OptionEntity
    {
        return new OptionEntity(
            id: $model->id,
            name: $model->name,
            parentId: $model->parent_id,
            hasChild: (bool) $model->has_child,
            isActive: (bool) $model->is_active,
            isDeleted: $model->trashed(),
            deletedAt: $model->deleted_at ? new DateTimeImmutable($model->deleted_at) : null,
            createdAt: new DateTimeImmutable($model->created_at),
            updatedAt: new DateTimeImmutable($model->updated_at),
            createdBy: $model->created_by,
            updatedBy: $model->updated_by,
            deletedBy: $model->deleted_by
        );
    }

    public function bulkDeleteOptions(array $ids, bool $permanent, ?string $deletedBy): array
    {
        $response = [
            'totalCount' => count($ids),
            'successCount' => 0,
            'failedCount' => 0,
            'success' => true,
            'message' => '',
            'errors' => []
        ];

        DB::beginTransaction();

        try {
            foreach ($ids as $id) {
                try {
                    $option = EloquentOption::withTrashed()
                        ->with('children')
                        ->find($id);

                    if (!$option) {
                        $response['failedCount']++;
                        $response['errors'][] = [
                            'id' => $id,
                            'error' => 'Option not found'
                        ];
                        $response['success'] = false;
                        continue;
                    }

                    // Check for active children
                    $hasActiveChildren = $option->children->filter(fn($c) => !$c->trashed())->count() > 0;

                    if ($permanent) {
                        // Permanent delete: cannot have children
                        if ($hasActiveChildren) {
                            $response['failedCount']++;
                            $response['errors'][] = [
                                'id' => $id,
                                'error' => "Cannot permanently delete option '{$option->name}' because it has child options. Please delete all child options first."
                            ];
                            $response['success'] = false;
                            continue;
                        }

                        // Also handle soft-deleted children
                        $softDeletedChildren = $option->children->filter(fn($c) => $c->trashed());
                        foreach ($softDeletedChildren as $child) {
                            $child->forceDelete();
                        }

                        $option->forceDelete();
                    } else {
                        // Soft delete
                        $option->deleted_by = $deletedBy;
                        $option->save();
                        $option->delete();

                        // Update parent's has_child flag
                        if ($option->parent_id) {
                            $parent = EloquentOption::find($option->parent_id);
                            if ($parent) {
                                $remainingChildren = EloquentOption::where('parent_id', $parent->id)
                                    ->whereNull('deleted_at')
                                    ->count();
                                $parent->has_child = $remainingChildren > 0;
                                $parent->updated_by = $deletedBy;
                                $parent->save();
                            }
                        }
                    }

                    $response['successCount']++;
                } catch (\Exception $ex) {
                    $response['failedCount']++;
                    $response['errors'][] = [
                        'id' => $id,
                        'error' => $ex->getMessage()
                    ];
                    $response['success'] = false;
                }
            }

            DB::commit();
            $response['message'] = "Processed {$response['totalCount']} options. Success: {$response['successCount']}, Failed: {$response['failedCount']}";
        } catch (\Exception $ex) {
            DB::rollBack();
            $response['success'] = false;
            $response['message'] = "Bulk operation failed: {$ex->getMessage()}";
        }

        return $response;
    }

    public function bulkRestoreOptions(array $ids, ?string $restoredBy): array
    {
        $response = [
            'totalCount' => count($ids),
            'successCount' => 0,
            'failedCount' => 0,
            'success' => true,
            'message' => '',
            'errors' => []
        ];

        DB::beginTransaction();

        try {
            foreach ($ids as $id) {
                try {
                    $option = EloquentOption::withTrashed()
                        ->where('id', $id)
                        ->whereNotNull('deleted_at')
                        ->first();

                    if (!$option) {
                        $response['failedCount']++;
                        $response['errors'][] = [
                            'id' => $id,
                            'error' => 'Option not found or not deleted'
                        ];
                        $response['success'] = false;
                        continue;
                    }

                    $option->restore();
                    $option->deleted_by = null;
                    $option->updated_by = $restoredBy;
                    $option->save();

                    // Update parent's has_child flag
                    if ($option->parent_id) {
                        $parent = EloquentOption::find($option->parent_id);
                        if ($parent && !$parent->has_child) {
                            $parent->has_child = true;
                            $parent->updated_by = $restoredBy;
                            $parent->save();
                        }
                    }

                    $response['successCount']++;
                } catch (\Exception $ex) {
                    $response['failedCount']++;
                    $response['errors'][] = [
                        'id' => $id,
                        'error' => $ex->getMessage()
                    ];
                    $response['success'] = false;
                }
            }

            DB::commit();
            $response['message'] = "Processed {$response['totalCount']} options. Success: {$response['successCount']}, Failed: {$response['failedCount']}";
        } catch (\Exception $ex) {
            DB::rollBack();
            $response['success'] = false;
            $response['message'] = "Bulk restore failed: {$ex->getMessage()}";
        }

        return $response;
    }
}
