<?php

namespace Modules\Shared\Infrastructure\Services;

use Illuminate\Support\Facades\Redis;
use Modules\Shared\Application\Services\IOptionsService;
use Modules\Shared\Application\Repositories\IUserLogRepository;
use Modules\Shared\Application\Repositories\IUserRepository;
use Modules\Shared\Application\Repositories\IRolePermissionRepository;
use Modules\Shared\Application\Requests\Common\SelectOptionRequest;
use Modules\Shared\Application\Resources\Common\SelectOptionResource;
use Modules\Shared\Infrastructure\Helpers\LabelFormatter;
use Illuminate\Support\Facades\Log;

final class OptionsService implements IOptionsService
{
    private const CACHE_TTL = 3600; // seconds (1 hour)
    private \Predis\Client $redis;

    public function __construct(
        private IUserLogRepository $userLogRepository,
        private IUserRepository $userRepository,
        private IRolePermissionRepository $rolePermissionRepository
    ) {
        // Get Redis connection
        $this->redis = Redis::connection()->client();
    }

    /**
     * Get select options for a given type
     */
    public function getOptions(string $type, SelectOptionRequest $req): array
    {
        // Include where filters in cache key (like .NET version)
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

        // Try cache first
        $cached = $this->redis->get($cacheKey);

        if ($cached) {
            // Log cache hit if needed (commented out like .NET version)
            // Log::debug("Cache HIT for {$type}");
            return json_decode($cached, true);
        }

        // Log cache miss if needed (commented out like .NET version)
        // Log::debug("Cache MISS for {$type}, fetching from repository...");

        // Fetch from repositories based on type
        $result = match (strtolower($type)) {
            'userlogcollections' => $this->getUserLogCollections($req),
            'userlogactiontypes' => $this->getUserLogActionTypes($req),
            'userlogcreators'    => $this->getUserLogCreators($req),
            'usercreators'       => $this->getUserCreators($req),
            'userupdaters'       => $this->getUserUpdaters($req),
            'userdatetypes'      => $this->getUserDateTypes($req),
            'roles'              => $this->getAllRoles($req),
            'permissions'        => $this->getAllPermissions($req),
            default              => []
        };

        // Log result count if needed (like .NET version)
        // Log::debug("Repository returned " . count($result) . " items");

        // Normalize labels using LabelFormatter (like .NET version)
        $formattedResult = array_map(function ($item) {
            return [
                'value' => $item['value'],
                'label' => LabelFormatter::toReadable($item['label'])
            ];
        }, $result);

        // Cache the result
        $this->redis->setex($cacheKey, self::CACHE_TTL, json_encode($formattedResult));
        // Log::debug("Cached result for {$type}");

        return $formattedResult;
    }

    /**
     * Async version for interface compatibility
     */
    public function getOptionsAsync(string $type, SelectOptionRequest $req): array
    {
        return $this->getOptions($type, $req);
    }

    // ==================== Private helper methods ====================

    private function getUserLogCollections(SelectOptionRequest $req): array
    {
        // Log::debug("Calling userLogRepository->getDistinctModelNames");
        $items = $this->userLogRepository->getDistinctModelNames($req);
        return $this->formatSelectOptions($items);
    }

    private function getUserLogActionTypes(SelectOptionRequest $req): array
    {
        // Log::debug("Calling userLogRepository->getDistinctActionTypes");
        $items = $this->userLogRepository->getDistinctActionTypes($req);
        return $this->formatSelectOptions($items);
    }

    private function getUserLogCreators(SelectOptionRequest $req): array
    {
        // Log::debug("Calling userLogRepository->getDistinctCreators");
        $items = $this->userLogRepository->getDistinctCreators($req);
        return $this->formatSelectOptions($items);
    }

    private function getUserCreators(SelectOptionRequest $req): array
    {
        // Log::debug("Calling userRepository->getDistinctCreators");
        $items = $this->userRepository->getDistinctCreators($req);
        return $this->formatSelectOptions($items);
    }

    private function getUserUpdaters(SelectOptionRequest $req): array
    {
        // Log::debug("Calling userRepository->getDistinctUpdaters");
        $items = $this->userRepository->getDistinctUpdaters($req);
        return $this->formatSelectOptions($items);
    }

    private function getUserDateTypes(SelectOptionRequest $req): array
    {
        // Log::debug("Calling userRepository->getDistinctDateTypes");
        $items = $this->userRepository->getDistinctDateTypes($req);
        return $this->formatSelectOptions($items);
    }

    private function getAllRoles(SelectOptionRequest $req): array
    {
        // Log::debug("Calling rolePermissionRepository->getAllRoles");
        // Note: You need to implement this method in your RolePermissionRepository
        $roles = $this->rolePermissionRepository->getAllRoles();

        // Apply pagination and search manually since the repository might not support it
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
        $result = array_slice(array_values($result), $req->skip, $req->limit);

        return $result;
    }

    private function getAllPermissions(SelectOptionRequest $req): array
    {
        // Log::debug("Calling rolePermissionRepository->getAllPermissions");
        // Note: You need to implement this method in your RolePermissionRepository
        $permissions = $this->rolePermissionRepository->getAllPermissions();

        // Apply pagination and search manually since the repository might not support it
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
        $result = array_slice(array_values($result), $req->skip, $req->limit);

        return $result;
    }

    /**
     * Format items from repositories into standard value/label array
     */
    private function formatSelectOptions(array $items): array
    {
        $result = [];

        foreach ($items as $item) {
            if (is_array($item)) {
                // Handle array format
                $value = $item['value'] ?? $item['id'] ?? (string) $item;
                $label = $item['label'] ?? $item['name'] ?? (string) $item;
                $result[] = ['value' => (string) $value, 'label' => (string) $label];
            } elseif (is_object($item)) {
                // Handle object format
                if ($item instanceof SelectOptionResource) {
                    $result[] = $item->toArray();
                } else {
                    $value = $item->value ?? $item->id ?? (string) $item;
                    $label = $item->label ?? $item->name ?? (string) $item;
                    $result[] = ['value' => (string) $value, 'label' => (string) $label];
                }
            } else {
                // Handle scalar values
                $result[] = ['value' => (string) $item, 'label' => (string) $item];
            }
        }

        return $result;
    }
}
