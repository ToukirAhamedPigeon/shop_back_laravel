<?php

namespace Modules\Shared\Infrastructure\Services;

use Illuminate\Support\Facades\Redis;
use Modules\Shared\Application\Services\IOptionsService;
use Modules\Shared\Application\Repositories\IUserLogRepository;
use Modules\Shared\Application\Repositories\IUserRepository;
use Modules\Shared\Application\Repositories\IRolePermissionRepository;
use Modules\Shared\Application\Requests\Common\SelectOptionRequest;
use Modules\Shared\Infrastructure\Helpers\LabelFormatter;

class OptionsService implements IOptionsService
{
    private IUserLogRepository $userLogRepository;
    private IUserRepository $userRepository;
    private IRolePermissionRepository $rolePermissionRepository;
    private \Predis\Client $redis;
    private int $cacheTtl = 3600; // 1 hour in seconds

    public function __construct(
        IUserLogRepository $userLogRepository,
        IUserRepository $userRepository,
        IRolePermissionRepository $rolePermissionRepository
    ) {
        $this->userLogRepository = $userLogRepository;
        $this->userRepository = $userRepository;
        $this->rolePermissionRepository = $rolePermissionRepository;
        $this->redis = Redis::connection()->client();
    }

    /**
     * Get select options for a given type
     */
    public function getOptions(string $type, SelectOptionRequest $req): array
    {
        // Include Where in cache key (like .NET)
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
            // Cache hit
            // Log::debug("Cache HIT for {$type}");
            return json_decode($cached, true);
        }

        // Cache miss, fetch from repositories
        // Log::debug("Cache MISS for {$type}, fetching from repository...");

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

        // Log::debug("Repository returned " . count($result) . " items");

        // Normalize labels
        foreach ($result as &$item) {
            $item['label'] = LabelFormatter::toReadable($item['label']);
        }

        // Cache the result
        $this->redis->setex($cacheKey, $this->cacheTtl, json_encode($result));
        // Log::debug("Cached result for {$type}");

        return $result;
    }

    public function getOptionsAsync(string $type, SelectOptionRequest $req): array
    {
        return $this->getOptions($type, $req);
    }

    /**
     * Get user log collections (model names)
     */
    private function getUserLogCollections(SelectOptionRequest $req): array
    {
        // Log::debug("Calling userLogRepository->getDistinctModelNames");
        return $this->userLogRepository->getDistinctModelNames($req);
    }

    /**
     * Get user log action types
     */
    private function getUserLogActionTypes(SelectOptionRequest $req): array
    {
        // Log::debug("Calling userLogRepository->getDistinctActionTypes");
        return $this->userLogRepository->getDistinctActionTypes($req);
    }

    /**
     * Get user log creators
     */
    private function getUserLogCreators(SelectOptionRequest $req): array
    {
        // Log::debug("Calling userLogRepository->getDistinctCreators");
        return $this->userLogRepository->getDistinctCreators($req);
    }

    /**
     * Get user creators
     */
    private function getUserCreators(SelectOptionRequest $req): array
    {
        // Log::debug("Calling userRepository->getDistinctCreators");
        return $this->userRepository->getDistinctCreators($req);
    }

    /**
     * Get user updaters
     */
    private function getUserUpdaters(SelectOptionRequest $req): array
    {
        // Log::debug("Calling userRepository->getDistinctUpdaters");
        return $this->userRepository->getDistinctUpdaters($req);
    }

    /**
     * Get user date types
     */
    private function getUserDateTypes(SelectOptionRequest $req): array
    {
        // Log::debug("Calling userRepository->getDistinctDateTypes");
        return $this->userRepository->getDistinctDateTypes($req);
    }

    /**
     * Get all roles
     */
    private function getAllRoles(SelectOptionRequest $req): array
    {
        // Log::debug("Calling rolePermissionRepository->getAllRoles");
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
     * Get all permissions
     */
    private function getAllPermissions(SelectOptionRequest $req): array
    {
        // Log::debug("Calling rolePermissionRepository->getAllPermissions");
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
