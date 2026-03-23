<?php

namespace Modules\Shared\API\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Shared\Application\Services\IRoleService;
use Modules\Shared\Application\Requests\Role\RoleFilterRequest;
use Modules\Shared\Application\Requests\Role\CreateRoleRequest;
use Modules\Shared\Application\Requests\Role\UpdateRoleRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class RoleController extends Controller
{
    private IRoleService $service;

    public function __construct(IRoleService $service)
    {
        $this->service = $service;
    }

    /**
     * Get paginated list of roles
     *
     * POST /api/roles
     */
    public function getRoles(RoleFilterRequest $request): JsonResponse
    {
        $result = $this->service->getRoles($request);
        return response()->json($result);
    }

    /**
     * Get role by ID
     *
     * GET /api/roles/{id}
     */
    public function getRole(string $id): JsonResponse
    {
        $role = $this->service->getRole($id);
        if (!$role) {
            return response()->json(null, 404);
        }
        return response()->json($role);
    }

    /**
     * Get role for editing
     *
     * GET /api/roles/{id}/edit
     */
    public function getRoleForEdit(string $id): JsonResponse
    {
        $role = $this->service->getRoleForEdit($id);
        if (!$role) {
            return response()->json(null, 404);
        }
        return response()->json($role);
    }

    /**
     * Create new role(s)
     *
     * POST /api/roles/create
     */
    public function create(CreateRoleRequest $request): JsonResponse
    {
        $currentUserId = Auth::id();
        $result = $this->service->createRole($request, $currentUserId);

        return $result['success']
            ? response()->json($result)
            : response()->json($result, 400);
    }

    /**
     * Update role
     *
     * PUT /api/roles/{id}
     */
    public function update(string $id, UpdateRoleRequest $request): JsonResponse
    {
        $currentUserId = Auth::id();
        $result = $this->service->updateRole($id, $request, $currentUserId);

        return $result['success']
            ? response()->json($result)
            : response()->json($result, 400);
    }

    /**
     * Delete role
     *
     * DELETE /api/roles/{id}?permanent=false
     */
    public function deleteRole(string $id, Request $request): JsonResponse
    {
        $permanent = filter_var($request->query('permanent', 'false'), FILTER_VALIDATE_BOOLEAN);
        $currentUserId = Auth::id();

        $result = $this->service->deleteRole($id, $permanent, $currentUserId);

        if (!$result['success']) {
            return response()->json(['message' => $result['message']], 400);
        }

        return response()->json([
            'message' => $result['message'],
            'deleteType' => $result['deleteType']
        ]);
    }

    /**
     * Restore soft-deleted role
     *
     * POST /api/roles/{id}/restore
     */
    public function restoreRole(string $id): JsonResponse
    {
        $currentUserId = Auth::id();
        $result = $this->service->restoreRole($id, $currentUserId);

        return $result['success']
            ? response()->json(['message' => $result['message']])
            : response()->json(['message' => $result['message']], 400);
    }

    /**
     * Get delete eligibility info
     *
     * GET /api/roles/{id}/delete-info
     */
    public function getDeleteInfo(string $id): JsonResponse
    {
        $result = $this->service->checkDeleteEligibility($id);

        if (!$result['success']) {
            return response()->json(['message' => $result['message']], 404);
        }

        return response()->json([
            'canBePermanent' => $result['canBePermanent'],
            'message' => $result['message']
        ]);
    }
}
