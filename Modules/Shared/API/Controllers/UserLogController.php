<?php

namespace Modules\Shared\API\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Shared\Application\Services\IUserLogService;
use Modules\Shared\Application\Requests\UserLog\UserLogFilterRequest;
use Modules\Shared\Application\Requests\Common\SelectOptionRequest;

class UserLogController extends Controller
{
    public function __construct(private IUserLogService $service) {}

    public function getFiltered(UserLogFilterRequest $request): JsonResponse
    {
        return response()->json($this->service->getFiltered($request));
    }

    public function get(string $id): JsonResponse
    {
        $log = $this->service->getById($id);
        return $log ? response()->json($log) : response()->json([], 404);
    }

    public function collections(SelectOptionRequest $req): JsonResponse
    {
        return response()->json($this->service->getCollections($req));
    }

    public function actionTypes(SelectOptionRequest $req): JsonResponse
    {
        return response()->json($this->service->getActionTypes($req));
    }

    public function creators(SelectOptionRequest $req): JsonResponse
    {
        return response()->json($this->service->getCreators($req));
    }
}
