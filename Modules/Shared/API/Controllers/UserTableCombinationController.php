<?php

namespace Modules\Shared\API\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Shared\Application\Services\IUserTableCombinationService;
use Modules\Shared\Application\Requests\UserTableCombinationRequest;

class UserTableCombinationController extends Controller
{
    private IUserTableCombinationService $service;

    public function __construct(IUserTableCombinationService $service)
    {
        $this->service = $service;
    }

    /**
     * GET /user-table-combination?tableId=xxx&userId=xxx
     * Permission: read-admin-dashboard
     */
    public function get(Request $request): JsonResponse
    {
        $tableId = $request->query('tableId');
        $userId  = $request->query('userId');

        if (!$tableId || !$userId) {
            return response()->json([
                'message' => 'Missing tableId or userId'
            ], 400);
        }

        $showColumnCombinations = $this->service->get($tableId, $userId);

        return response()->json([
            'showColumnCombinations' => $showColumnCombinations
        ]);
    }

    /**
     * PUT /user-table-combination
     * Permission: read-admin-dashboard
     */
    public function update(UserTableCombinationRequest $request): JsonResponse
    {
        $authUser = $request->user();

        if (!$authUser) {
            return response()->json([
                'message' => 'Unauthenticated'
            ], 401);
        }

        // Explicit, IDE-safe, JWT-safe user ID
        $authUserId = (string) $authUser->getAuthIdentifier();

        $this->service->saveOrUpdate($request, $authUserId);

        return response()->json([
            'success' => true
        ]);
    }
}
