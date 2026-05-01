<?php

namespace Modules\Shared\API\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Shared\Application\Services\IPermissionService;
use Modules\Shared\Application\Requests\Permission\PermissionFilterRequest;
use Modules\Shared\Application\Requests\Permission\CreatePermissionRequest;
use Modules\Shared\Application\Requests\Permission\UpdatePermissionRequest;
use Modules\Shared\Application\Requests\Common\BulkOperationRequest;
use Illuminate\Support\Facades\Auth;

class PermissionController extends Controller
{
    private IPermissionService $service;

    public function __construct(IPermissionService $service)
    {
        $this->service = $service;
    }

    /**
     * Get paginated list of permissions
     *
     * POST /api/permissions
     */
    public function getPermissions(PermissionFilterRequest $request): JsonResponse
    {
        $result = $this->service->getPermissions($request);
        return response()->json($result);
    }

    /**
     * Get permission by ID
     *
     * GET /api/permissions/{id}
     */
    public function getPermission(string $id): JsonResponse
    {
        $permission = $this->service->getPermission($id);
        if (!$permission) {
            return response()->json(null, 404);
        }
        return response()->json($permission);
    }

    /**
     * Get permission for editing
     *
     * GET /api/permissions/{id}/edit
     */
    public function getPermissionForEdit(string $id): JsonResponse
    {
        $permission = $this->service->getPermissionForEdit($id);
        if (!$permission) {
            return response()->json(null, 404);
        }
        return response()->json($permission);
    }

    /**
     * Create new permission(s)
     *
     * POST /api/permissions/create
     */
    public function create(CreatePermissionRequest $request): JsonResponse
    {
        $currentUserId = Auth::id();
        $result = $this->service->createPermission($request, $currentUserId);

        return $result['success']
            ? response()->json($result)
            : response()->json($result, 400);
    }

    /**
     * Update permission
     *
     * PUT /api/permissions/{id}
     */
    public function update(string $id, UpdatePermissionRequest $request): JsonResponse
    {
        $currentUserId = Auth::id();
        $result = $this->service->updatePermission($id, $request, $currentUserId);

        return $result['success']
            ? response()->json($result)
            : response()->json($result, 400);
    }

    /**
     * Delete permission
     *
     * DELETE /api/permissions/{id}?permanent=false
     */
    public function deletePermission(string $id, Request $request): JsonResponse
    {
        $permanent = filter_var($request->query('permanent', 'false'), FILTER_VALIDATE_BOOLEAN);
        $currentUserId = Auth::id();

        $result = $this->service->deletePermission($id, $permanent, $currentUserId);

        if (!$result['success']) {
            return response()->json(['message' => $result['message']], 400);
        }

        return response()->json([
            'message' => $result['message'],
            'deleteType' => $result['deleteType']
        ]);
    }

    /**
     * Restore soft-deleted permission
     *
     * POST /api/permissions/{id}/restore
     */
    public function restorePermission(string $id): JsonResponse
    {
        $currentUserId = Auth::id();
        $result = $this->service->restorePermission($id, $currentUserId);

        return $result['success']
            ? response()->json(['message' => $result['message']])
            : response()->json(['message' => $result['message']], 400);
    }

    /**
     * Get delete eligibility info
     *
     * GET /api/permissions/{id}/delete-info
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
     /**
     * Bulk delete permissions (soft or permanent)
     *
     * POST /api/permissions/bulk-delete
     */
    public function bulkDelete(BulkOperationRequest $request): JsonResponse
    {
        // Validate and convert IDs
        $validation = $request->validateIds();

        if (!$validation['isValid']) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid UUID format for IDs: ' . implode(', ', $validation['invalidIds'])
            ], 400);
        }

        $guids = $request->getGuids();
        $currentUserId = Auth::id();

        $result = $this->service->bulkDeletePermissions($guids, $request->permanent, $currentUserId);

        return response()->json($result);
    }

    /**
     * Bulk restore soft-deleted permissions
     *
     * POST /api/permissions/bulk-restore
     */
    public function bulkRestore(BulkOperationRequest $request): JsonResponse
    {
        // Validate and convert IDs
        $validation = $request->validateIds();

        if (!$validation['isValid']) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid UUID format for IDs: ' . implode(', ', $validation['invalidIds'])
            ], 400);
        }

        $guids = $request->getGuids();
        $currentUserId = Auth::id();

        $result = $this->service->bulkRestorePermissions($guids, $currentUserId);

        return response()->json($result);
    }
}
