<?php

namespace Modules\Shared\API\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Shared\Application\Services\IUserService;
use Modules\Shared\Application\Requests\Users\UserFilterRequest;

class UserController extends Controller
{
    public function __construct(private IUserService $service) {}

    public function getUsers(UserFilterRequest $request): JsonResponse
    {
        return response()->json(
            $this->service->getUsers($request)
        );
    }

    public function get(string $id): JsonResponse
    {
        $user = $this->service->getById($id);
        return $user
            ? response()->json($user)
            : response()->json([], 404);
    }
}
