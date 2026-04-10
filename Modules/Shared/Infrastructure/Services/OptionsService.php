<?php

namespace Modules\Shared\Infrastructure\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;
use DateTimeImmutable;
use Modules\Shared\Application\Services\IOptionsService;
use Modules\Shared\Application\Repositories\IOptionRepository;
use Modules\Shared\Application\Repositories\IUserLogRepository;
use Modules\Shared\Application\Repositories\IUserRepository;
use Modules\Shared\Application\Repositories\IRolePermissionRepository;
use Modules\Shared\Application\Requests\Common\SelectOptionRequest;
use Modules\Shared\Application\Requests\Option\OptionFilterRequest;
use Modules\Shared\Application\Requests\Option\CreateOptionRequest;
use Modules\Shared\Application\Requests\Option\UpdateOptionRequest;
use Modules\Shared\Application\Resources\Option\OptionResource;
use Modules\Shared\Domain\Entities\Option;
use Modules\Shared\Infrastructure\Helpers\UserLogHelper;
use Modules\Shared\Infrastructure\Helpers\LabelFormatter;
use Illuminate\Support\Facades\Redis;

class OptionsService implements IOptionsService
{
    private IOptionRepository $optionRepository;
    private IUserLogRepository $userLogRepository;
    private IUserRepository $userRepository;
    private IRolePermissionRepository $rolePermissionRepository;
    private UserLogHelper $userLogHelper;
    private int $cacheTtl = 3600; // 1 hour

    public function __construct(
        IOptionRepository $optionRepository,
        IUserLogRepository $userLogRepository,
        IUserRepository $userRepository,
        IRolePermissionRepository $rolePermissionRepository,
        UserLogHelper $userLogHelper
    ) {
        $this->optionRepository = $optionRepository;
        $this->userLogRepository = $userLogRepository;
        $this->userRepository = $userRepository;
        $this->rolePermissionRepository = $rolePermissionRepository;
        $this->userLogHelper = $userLogHelper;
    }

    /**
     * Get select options for a given type
     */
    public function getOptions(string $type, SelectOptionRequest $req): array
    {
        $whereJson = !empty($req->where) ? json_encode($req->where) : '';
        $cacheKey = sprintf(
            'Options:%s:%s:%d:%d:%s:%s:%s',
            strtolower($type),
            $req->search ?? '',
            $req->skip ?? 0,
            $req->limit ?? 250,
            $req->sortBy ?? '',
            $req->sortOrder ?? 'asc',
            md5($whereJson)
        );

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($type, $req) {
            $result = match (strtolower($type)) {
                'userlogcollections' => $this->formatSelectOptionResult($this->userLogRepository->getDistinctModelNames($req)),
                'userlogactiontypes' => $this->formatSelectOptionResult($this->userLogRepository->getDistinctActionTypes($req)),
                'userlogcreators'    => $this->formatSelectOptionResult($this->userLogRepository->getDistinctCreators($req)),
                'usercreators'       => $this->formatSelectOptionResult($this->userRepository->getDistinctCreators($req)),
                'userupdaters'       => $this->formatSelectOptionResult($this->userRepository->getDistinctUpdaters($req)),
                'userdatetypes'      => $this->formatSelectOptionResult($this->userRepository->getDistinctDateTypes($req)),
                'roles'              => $this->getAllRoles($req),
                'permissions'        => $this->getAllPermissions($req),
                default              => []
            };

            foreach ($result as &$item) {
                $item['label'] = LabelFormatter::toReadable($item['label']);
            }

            return $result;
        });
    }

    /**
     * Get paginated options for CRUD list
     */
    public function getOptionsPaginated(OptionFilterRequest $request): array
    {
        return $this->optionRepository->getFilteredOptions($request);
    }

    /**
     * Get single option by ID
     */
    public function getOption(string $id): ?array
    {
        $option = $this->optionRepository->getOptionById($id);
        if (!$option) return null;

        // Get parent name
        $parentName = null;
        if ($option->parentId) {
            $parent = $this->optionRepository->getOptionById($option->parentId);
            $parentName = $parent ? $parent->name : null;
        }

        // Get user names
        $userIds = array_filter([$option->createdBy, $option->updatedBy, $option->deletedBy]);
        $users = [];
        if (!empty($userIds)) {
            $users = DB::table('users')
                ->whereIn('id', $userIds)
                ->pluck('name', 'id')
                ->toArray();
        }

        $resource = new OptionResource(
            $option,
            $parentName,
            $users[$option->createdBy] ?? null,
            $users[$option->updatedBy] ?? null,
            $users[$option->deletedBy] ?? null
        );

        return $resource->toArray();
    }

    /**
     * Get option for editing
     */
    public function getOptionForEdit(string $id): ?array
    {
        return $this->getOption($id);
    }

    /**
     * Create new option(s)
     */
    public function createOption(CreateOptionRequest $request, ?string $createdBy): array
    {
        $optionNames = $request->getOptionNames();

        if (empty($optionNames)) {
            return ['success' => false, 'message' => 'At least one valid option name is required'];
        }

        // Check for duplicates in request
        if (count($optionNames) !== count(array_unique($optionNames))) {
            return ['success' => false, 'message' => 'Duplicate option names found in request'];
        }

        $hasChild = $request->getHasChildBool();
        $isActive = $request->getIsActiveBool();
        $parentId = $request->getParentId();
        $createdByGuid = !empty($createdBy) && Str::isUuid($createdBy) ? $createdBy : null;

        // Validate parent if provided
        if ($parentId) {
            $parent = $this->optionRepository->getOptionById($parentId);
            if (!$parent) {
                return ['success' => false, 'message' => 'Parent option not found'];
            }
            if (!$parent->hasChild) {
                return ['success' => false, 'message' => 'Selected parent option does not allow children'];
            }
        }

        DB::beginTransaction();

        try {
            $createdOptions = [];
            $existingOptions = [];

            foreach ($optionNames as $optionName) {
                // Check for existing option with same name and parent
                if ($this->optionRepository->optionExists($optionName, $parentId)) {
                    $existingOptions[] = $optionName;
                    continue;
                }

                $option = new Option(
                    id: (string) Str::uuid(),
                    name: $optionName,
                    parentId: $parentId,
                    hasChild: $hasChild,
                    isActive: $isActive,
                    isDeleted: false,
                    createdAt: Carbon::now()->toDateTimeImmutable(),
                    updatedAt: Carbon::now()->toDateTimeImmutable(),
                    createdBy: $createdByGuid,
                    updatedBy: $createdByGuid
                );

                $this->optionRepository->createOption($option);
                $createdOptions[] = $option;

                // If this option has a parent, update parent's HasChild flag if needed
                if ($parentId) {
                    $parent = $this->optionRepository->getOptionById($parentId);
                    if ($parent && !$parent->hasChild) {
                        $parent->hasChild = true;
                        $parent->updatedAt = Carbon::now()->toDateTimeImmutable();
                        $parent->updatedBy = $createdByGuid;
                        $this->optionRepository->updateOption($parent);
                    }
                }
            }

            // Clear cache after create
            $this->clearOptionsCache();

            // Log the action
            if (!empty($createdOptions)) {
                $afterSnapshot = [
                    'Options' => array_map(function($o) {
                        return [
                            'id' => $o->id,
                            'name' => $o->name,
                            'parentId' => $o->parentId,
                            'hasChild' => $o->hasChild,
                            'isActive' => $o->isActive,
                        ];
                    }, $createdOptions),
                    'ParentId' => $parentId,
                    'HasChild' => $hasChild
                ];

                $this->userLogHelper->log(
                    actionType: 'Create',
                    detail: count($createdOptions) . ' option(s) created: ' . implode(', ', array_map(fn($o) => $o->name, $createdOptions)) .
                        (!empty($existingOptions) ? ' (Skipped existing: ' . implode(', ', $existingOptions) . ')' : ''),
                    changes: json_encode(['before' => null, 'after' => $afterSnapshot]),
                    modelName: 'Option',
                    modelId: $createdOptions[0]->id,
                    userId: $createdByGuid ?? $createdOptions[0]->id
                );
            }

            DB::commit();

            $message = count($createdOptions) . ' option(s) created successfully';
            if (!empty($existingOptions)) {
                $message .= ' (Skipped existing: ' . implode(', ', $existingOptions) . ')';
            }

            return ['success' => true, 'message' => $message];
        } catch (\Exception $ex) {
            DB::rollBack();
            Log::error('Error creating options: ' . $ex->getMessage(), ['trace' => $ex->getTraceAsString()]);
            return ['success' => false, 'message' => 'Error creating options: ' . $ex->getMessage()];
        }
    }

    /**
     * Update an option
     */
    public function updateOption(string $id, UpdateOptionRequest $request, ?string $updatedBy): array
    {
        $option = $this->optionRepository->getOptionById($id);
        if (!$option) {
            return ['success' => false, 'message' => 'Option not found'];
        }

        // Check uniqueness (excluding current option)
        if ($option->name !== $request->name && $this->optionRepository->optionExists($request->name, $request->getParentId(), $id)) {
            return ['success' => false, 'message' => 'Option name already exists with the same parent'];
        }

        $newParentId = $request->getParentId();
        $hasChild = $request->getHasChildBool();
        $isActive = $request->getIsActiveBool();

        // Validate parent if provided
        if ($newParentId) {
            // Cannot set parent to itself
            if ($newParentId === $id) {
                return ['success' => false, 'message' => 'Cannot set an option as its own parent'];
            }

            // Check for circular reference
            if ($this->wouldCreateCircularReference($id, $newParentId)) {
                return ['success' => false, 'message' => 'This would create a circular reference in the option hierarchy'];
            }

            $parent = $this->optionRepository->getOptionById($newParentId);
            if (!$parent) {
                return ['success' => false, 'message' => 'Parent option not found'];
            }
            if (!$parent->hasChild && $hasChild) {
                return ['success' => false, 'message' => 'Selected parent option does not allow children'];
            }
        }

        // Get current state for logging
        $beforeSnapshot = [
            'id' => $option->id,
            'name' => $option->name,
            'parentId' => $option->parentId,
            'hasChild' => $option->hasChild,
            'isActive' => $option->isActive,
        ];

        $updatedByGuid = !empty($updatedBy) && Str::isUuid($updatedBy) ? $updatedBy : null;
        $oldParentId = $option->parentId;

        DB::beginTransaction();

        try {
            // Update option
            $option->name = $request->name;
            $option->parentId = $newParentId;
            $option->hasChild = $hasChild;
            $option->isActive = $isActive;
            $option->updatedAt = Carbon::now()->toDateTimeImmutable();
            $option->updatedBy = $updatedByGuid;

            $this->optionRepository->updateOption($option);

            // Handle parent relationships
            if ($oldParentId !== $newParentId) {
                // Update old parent's HasChild flag
                if ($oldParentId) {
                    $oldParent = $this->optionRepository->getOptionById($oldParentId);
                    if ($oldParent) {
                        $remainingChildren = $this->optionRepository->getChildrenCount($oldParentId);
                        $oldParent->hasChild = $remainingChildren > 0;
                        $oldParent->updatedAt = Carbon::now()->toDateTimeImmutable();
                        $oldParent->updatedBy = $updatedByGuid;
                        $this->optionRepository->updateOption($oldParent);
                    }
                }

                // Update new parent's HasChild flag
                if ($newParentId) {
                    $newParent = $this->optionRepository->getOptionById($newParentId);
                    if ($newParent && !$newParent->hasChild) {
                        $newParent->hasChild = true;
                        $newParent->updatedAt = Carbon::now()->toDateTimeImmutable();
                        $newParent->updatedBy = $updatedByGuid;
                        $this->optionRepository->updateOption($newParent);
                    }
                }
            }

            // Clear cache after update
            $this->clearOptionsCache();

            // Log the action
            $afterSnapshot = [
                'id' => $option->id,
                'name' => $option->name,
                'parentId' => $option->parentId,
                'hasChild' => $option->hasChild,
                'isActive' => $option->isActive,
            ];

            $this->userLogHelper->log(
                actionType: 'Update',
                detail: "Option '{$option->name}' was updated",
                changes: json_encode(['before' => $beforeSnapshot, 'after' => $afterSnapshot]),
                modelName: 'Option',
                modelId: $option->id,
                userId: $updatedByGuid ?? $option->id
            );

            DB::commit();

            return ['success' => true, 'message' => 'Option updated successfully'];
        } catch (\Exception $ex) {
            DB::rollBack();
            Log::error('Error updating option: ' . $ex->getMessage(), ['option_id' => $id, 'trace' => $ex->getTraceAsString()]);
            return ['success' => false, 'message' => 'Error updating option: ' . $ex->getMessage()];
        }
    }

    /**
     * Delete an option (soft or permanent)
     */
    public function deleteOption(string $id, bool $permanent, ?string $currentUserId): array
    {
        $option = $this->optionRepository->getOptionById($id);
        if (!$option) {
            return ['success' => false, 'message' => 'Option not found', 'deleteType' => 'none'];
        }

        if ($option->isDeleted) {
            return ['success' => false, 'message' => 'Option is already deleted', 'deleteType' => 'none'];
        }

        $hasChildren = $this->optionRepository->optionHasChildren($id);
        $childrenCount = $this->optionRepository->getChildrenCount($id);

        // Determine delete type
        $deleteType = 'soft';
        if ($permanent) {
            if ($hasChildren) {
                return ['success' => false, 'message' => 'Cannot permanently delete an option that has children. Please delete or reassign children first.', 'deleteType' => 'none'];
            }
            $deleteType = 'permanent';
        }

        $deletedBy = !empty($currentUserId) && Str::isUuid($currentUserId) ? $currentUserId : null;

        DB::beginTransaction();

        try {
            $this->optionRepository->deleteOption($id, $deleteType === 'permanent', $deletedBy);

            // If soft delete and this option had a parent, update parent's HasChild flag
            if ($deleteType === 'soft' && $option->parentId) {
                $parent = $this->optionRepository->getOptionById($option->parentId);
                if ($parent) {
                    $remainingChildren = $this->optionRepository->getChildrenCount($option->parentId);
                    if ($remainingChildren === 0) {
                        $parent->hasChild = false;
                        $parent->updatedAt = Carbon::now()->toDateTimeImmutable();
                        $parent->updatedBy = $deletedBy;
                        $this->optionRepository->updateOption($parent);
                    }
                }
            }

            // Clear cache after delete
            $this->clearOptionsCache();

            // Log the action
            $this->userLogHelper->log(
                actionType: 'Delete',
                detail: "Option '{$option->name}' was " . ($deleteType === 'permanent' ? 'permanently' : 'soft') . " deleted. Children affected: {$childrenCount}",
                changes: json_encode([
                    'before' => [
                        'id' => $option->id,
                        'name' => $option->name,
                        'parentId' => $option->parentId,
                        'hasChild' => $option->hasChild,
                        'isActive' => $option->isActive,
                        'isDeleted' => $option->isDeleted,
                    ],
                    'after' => [
                        'isDeleted' => true,
                        'deletedAt' => Carbon::now()->toISOString(),
                        'deletedBy' => $deletedBy
                    ]
                ]),
                modelName: 'Option',
                modelId: $option->id,
                userId: $deletedBy ?? $option->id
            );

            DB::commit();

            return [
                'success' => true,
                'message' => "Option " . ($deleteType === 'permanent' ? 'permanently' : 'soft') . " deleted successfully",
                'deleteType' => $deleteType
            ];
        } catch (\Exception $ex) {
            DB::rollBack();
            Log::error('Error deleting option: ' . $ex->getMessage(), ['option_id' => $id, 'trace' => $ex->getTraceAsString()]);
            return ['success' => false, 'message' => 'Error deleting option: ' . $ex->getMessage(), 'deleteType' => 'none'];
        }
    }

    /**
     * Restore a soft-deleted option
     */
    public function restoreOption(string $id, ?string $currentUserId): array
    {
        $option = $this->optionRepository->getOptionById($id);
        if (!$option) {
            return ['success' => false, 'message' => 'Option not found'];
        }

        if (!$option->isDeleted) {
            return ['success' => false, 'message' => 'Option is not deleted'];
        }

        $restoredBy = !empty($currentUserId) && Str::isUuid($currentUserId) ? $currentUserId : null;

        DB::beginTransaction();

        try {
            $this->optionRepository->restoreOption($id);

            // If this option has a parent, update parent's HasChild flag
            if ($option->parentId) {
                $parent = $this->optionRepository->getOptionById($option->parentId);
                if ($parent && !$parent->hasChild) {
                    $parent->hasChild = true;
                    $parent->updatedAt = Carbon::now()->toDateTimeImmutable();
                    $parent->updatedBy = $restoredBy;
                    $this->optionRepository->updateOption($parent);
                }
            }

            // Clear cache after restore
            $this->clearOptionsCache();

            // Log the action
            $this->userLogHelper->log(
                actionType: 'Restore',
                detail: "Option '{$option->name}' was restored",
                changes: json_encode([
                    'before' => ['isDeleted' => true],
                    'after' => ['isDeleted' => false]
                ]),
                modelName: 'Option',
                modelId: $option->id,
                userId: $restoredBy ?? $option->id
            );

            DB::commit();

            return ['success' => true, 'message' => 'Option restored successfully'];
        } catch (\Exception $ex) {
            DB::rollBack();
            Log::error('Error restoring option: ' . $ex->getMessage(), ['option_id' => $id, 'trace' => $ex->getTraceAsString()]);
            return ['success' => false, 'message' => 'Error restoring option: ' . $ex->getMessage()];
        }
    }

    /**
     * Check if an option can be permanently deleted
     */
    public function checkDeleteEligibility(string $id): array
    {
        $option = $this->optionRepository->getOptionById($id);
        if (!$option) {
            return [
                'success' => false,
                'message' => 'Option not found',
                'canBePermanent' => false,
                'hasChildren' => false,
                'childrenCount' => 0
            ];
        }

        if ($option->isDeleted) {
            return [
                'success' => true,
                'message' => 'Option is already deleted',
                'canBePermanent' => false,
                'hasChildren' => false,
                'childrenCount' => 0
            ];
        }

        $hasChildren = $this->optionRepository->optionHasChildren($id);
        $childrenCount = $this->optionRepository->getChildrenCount($id);

        $canBePermanent = !$hasChildren;
        $message = $canBePermanent
            ? 'Option can be permanently deleted'
            : 'Option must be soft deleted because it has child options. Only Developer type users can delete options with children.';

        return [
            'success' => true,
            'message' => $message,
            'canBePermanent' => $canBePermanent,
            'hasChildren' => $hasChildren,
            'childrenCount' => $childrenCount
        ];
    }

    /**
     * Get parent options for dropdown
     */
    public function getParentOptions(SelectOptionRequest $request): array
    {
        $cacheKey = "ParentOptions:{$request->search}:{$request->skip}:{$request->limit}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($request) {
            $parents = $this->optionRepository->getParentOptions(true);

            $query = collect($parents);

            if (!empty($request->search)) {
                $query = $query->filter(function($parent) use ($request) {
                    return stripos($parent['name'], $request->search) !== false;
                });
            }

            $result = $query->skip($request->skip)
                ->take($request->limit > 0 ? $request->limit : PHP_INT_MAX)
                ->map(function($parent) {
                    return [
                        'value' => $parent['id'],
                        'label' => $parent['name']
                    ];
                })
                ->values()
                ->toArray();

            return $result;
        });
    }

    /**
     * Clear options cache - Redis pattern deletion
     */
    private function clearOptionsCache(): void
    {
        try {
            // Get Redis connection
            $redis = Redis::connection();

            // Delete all Options:* keys
            $optionsKeys = $redis->keys('Options:*');
            if (!empty($optionsKeys)) {
                $redis->del($optionsKeys);
                Log::info('Cleared ' . count($optionsKeys) . ' Options cache keys');
            }

            // Delete all ParentOptions:* keys
            $parentKeys = $redis->keys('ParentOptions:*');
            if (!empty($parentKeys)) {
                $redis->del($parentKeys);
                Log::info('Cleared ' . count($parentKeys) . ' ParentOptions cache keys');
            }

            // Also clear using Laravel Cache facade for any tagged caches
            Cache::flush();
        } catch (\Exception $e) {
            Log::error('Error clearing options cache: ' . $e->getMessage());
        }
    }

    /**
     * Check for circular reference in parent-child relationship
     */
    private function wouldCreateCircularReference(string $optionId, string $newParentId): bool
    {
        $currentParentId = $newParentId;
        $visitedIds = [$optionId];

        while ($currentParentId) {
            if (in_array($currentParentId, $visitedIds)) {
                return true;
            }

            $visitedIds[] = $currentParentId;

            $parent = $this->optionRepository->getOptionById($currentParentId);
            if (!$parent || !$parent->parentId) {
                break;
            }

            $currentParentId = $parent->parentId;
        }

        return false;
    }

    /**
     * Format select option results
     */
    private function formatSelectOptionResult($items): array
    {
        $result = [];
        foreach ($items as $item) {
            if (is_array($item) && isset($item['value']) && isset($item['label'])) {
                $result[] = $item;
            } elseif (is_string($item)) {
                $result[] = ['value' => $item, 'label' => $item];
            }
        }
        return $result;
    }

    /**
     * Get all roles for select options
     */
    private function getAllRoles(SelectOptionRequest $req): array
    {
        $roles = $this->rolePermissionRepository->getAllRoles();

        $result = [];
        foreach ($roles as $role) {
            $result[] = ['value' => $role, 'label' => $role];
        }

        // Apply search filter
        if (!empty($req->search)) {
            $result = array_filter($result, function($item) use ($req) {
                return stripos($item['label'], $req->search) !== false;
            });
        }

        // Apply sorting
        usort($result, function($a, $b) use ($req) {
            $cmp = strcmp($a['label'], $b['label']);
            return $req->sortOrder === 'desc' ? -$cmp : $cmp;
        });

        // Apply pagination
        return array_slice(array_values($result), $req->skip, $req->limit);
    }

    /**
     * Get all permissions for select options
     */
    private function getAllPermissions(SelectOptionRequest $req): array
    {
        $permissions = $this->rolePermissionRepository->getAllPermissions();

        $result = [];
        foreach ($permissions as $permission) {
            $result[] = ['value' => $permission, 'label' => $permission];
        }

        // Apply search filter
        if (!empty($req->search)) {
            $result = array_filter($result, function($item) use ($req) {
                return stripos($item['label'], $req->search) !== false;
            });
        }

        // Apply sorting
        usort($result, function($a, $b) use ($req) {
            $cmp = strcmp($a['label'], $b['label']);
            return $req->sortOrder === 'desc' ? -$cmp : $cmp;
        });

        // Apply pagination
        return array_slice(array_values($result), $req->skip, $req->limit);
    }
}
