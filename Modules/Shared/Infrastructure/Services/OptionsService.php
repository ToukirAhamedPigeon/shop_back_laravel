<?php

namespace Modules\Shared\Infrastructure\Services;

use Illuminate\Support\Facades\Cache;
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
    private int $cacheTtl = 3600; // 1 hour in seconds

    public function __construct(
        IUserLogRepository $userLogRepository,
        IUserRepository $userRepository,
        IRolePermissionRepository $rolePermissionRepository
    ) {
        $this->userLogRepository = $userLogRepository;
        $this->userRepository = $userRepository;
        $this->rolePermissionRepository = $rolePermissionRepository;
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

        // Use Laravel's Cache facade (automatically uses Redis)
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

            // Normalize labels
            foreach ($result as &$item) {
                $item['label'] = LabelFormatter::toReadable($item['label']);
            }

            return $result;
        });
    }

    public function getOptionsAsync(string $type, SelectOptionRequest $req): array
    {
        return $this->getOptions($type, $req);
    }

    /**
     * Format SelectOptionResource objects to arrays
     */
    private function formatSelectOptionResult($items): array
    {
        $result = [];

        foreach ($items as $item) {
            if (is_object($item) && method_exists($item, 'toArray')) {
                // If it's a SelectOptionResource with toArray method
                $result[] = $item->toArray();
            } elseif (is_object($item) && isset($item->value) && isset($item->label)) {
                // If it's an object with value/label properties
                $result[] = ['value' => (string) $item->value, 'label' => (string) $item->label];
            } elseif (is_array($item) && isset($item['value']) && isset($item['label'])) {
                // If it's already an array with value/label
                $result[] = $item;
            } elseif (is_string($item)) {
                // If it's just a string
                $result[] = ['value' => $item, 'label' => $item];
            }
        }

        return $result;
    }

    /**
     * Get all roles
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
     * Get all permissions
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
