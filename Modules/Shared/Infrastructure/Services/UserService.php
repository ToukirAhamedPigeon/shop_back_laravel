<?php

namespace Modules\Shared\Application\Services;

use Modules\Shared\Application\Repositories\IUserRepository;
use Modules\Shared\Application\Resources\Users\UserResource;

final class UserService implements IUserService
{
    public function __construct(private IUserRepository $repo) {}

    public function getUsers($request): array
    {
        return $this->repo->getFiltered($request);
    }

    public function getById(string $id): ?array
    {
        $user = $this->repo->findById($id);
        return $user ? (new UserResource($user))->toArray(request()) : null;
    }
}
