<?php

namespace Modules\Shared\Infrastructure\Services;

use Modules\Shared\Application\Repositories\IUserLogRepository;
use Modules\Shared\Application\Services\IUserLogService;

final class UserLogService implements IUserLogService
{
    private IUserLogRepository $repo;

    public function __construct(IUserLogRepository $repo)
    {
        $this->repo = $repo;
    }

    public function getFiltered($req): array
    {
        return $this->repo->getFiltered($req);
    }

    public function getById(string $id): ?array
    {
        $log = $this->repo->findById($id);
        return $log ? (array)$log : null;
    }

    // public function getCollections($req): array
    // {
    //     return Cache::remember('userlog.collections', 3600, fn() =>
    //         $this->repo->getCollections($req)
    //     );
    // }

    // public function getActionTypes($req): array
    // {
    //     return Cache::remember('userlog.actionTypes', 3600, fn() =>
    //         $this->repo->getActionTypes($req)
    //     );
    // }

    // public function getCreators($req): array
    // {
    //     return Cache::remember('userlog.creators', 3600, fn() =>
    //         $this->repo->getCreators($req)
    //     );
    // }
}
