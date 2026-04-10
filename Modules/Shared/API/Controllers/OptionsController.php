<?php

namespace Modules\Shared\API\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Shared\Application\Services\IOptionsService;
use Modules\Shared\Application\Requests\Common\SelectOptionRequest;
use Illuminate\Http\Request;
use Modules\Shared\Application\Requests\Option\OptionFilterRequest;
use Modules\Shared\Application\Requests\Option\CreateOptionRequest;
use Modules\Shared\Application\Requests\Option\UpdateOptionRequest;
use Illuminate\Support\Facades\Auth;

class OptionsController extends Controller
{
    private IOptionsService $service;

    public function __construct(IOptionsService $service)
    {
        $this->service = $service;
    }

    /**
     * Generic select options endpoint.
     *
     * POST /api/Options/{type}
     *
     * @param string $type The option type
     * @param SelectOptionRequest $request The request with defaults from prepareForValidation
     * @return JsonResponse
     */
    public function getOptions(string $type, SelectOptionRequest $request): JsonResponse
    {
        $options = $this->service->getOptions($type, $request);

        return response()->json($options);
    }

    /**
     * Async version for interface compatibility
     */
    public function getOptionsAsync(string $type, SelectOptionRequest $request): JsonResponse
    {
        return $this->getOptions($type, $request);
    }

    /**
     * Get filtered list of options
     *
     * POST /api/options/list
     */
    public function getOptionsList(OptionFilterRequest $request): JsonResponse
    {
        $result = $this->service->getOptionsPaginated($request);
        return response()->json($result);
    }

    /**
     * Get single option by ID
     *
     * GET /api/options/{id}
     */
    public function getOption(string $id): JsonResponse
    {
        $option = $this->service->getOption($id);
        if (!$option) {
            return response()->json(null, 404);
        }
        return response()->json($option);
    }

    /**
     * Get option for editing
     *
     * GET /api/options/{id}/edit
     */
    public function getOptionForEdit(string $id): JsonResponse
    {
        $option = $this->service->getOptionForEdit($id);
        if (!$option) {
            return response()->json(null, 404);
        }
        return response()->json($option);
    }

    /**
     * Create new option(s)
     *
     * POST /api/options/create
     */
    public function create(CreateOptionRequest $request): JsonResponse
    {
        $currentUserId = Auth::id();
        $result = $this->service->createOption($request, $currentUserId);

        return $result['success']
            ? response()->json($result)
            : response()->json($result, 400);
    }

    /**
     * Update an option
     *
     * PUT /api/options/{id}
     */
    public function update(string $id, UpdateOptionRequest $request): JsonResponse
    {
        $currentUserId = Auth::id();
        $result = $this->service->updateOption($id, $request, $currentUserId);

        return $result['success']
            ? response()->json($result)
            : response()->json($result, 400);
    }

    /**
     * Delete an option (soft or permanent)
     *
     * DELETE /api/options/{id}?permanent=false
     */
    public function deleteOption(string $id, Request $request): JsonResponse
    {
        $permanent = filter_var($request->query('permanent', 'false'), FILTER_VALIDATE_BOOLEAN);
        $currentUserId = Auth::id();

        $result = $this->service->deleteOption($id, $permanent, $currentUserId);

        if (!$result['success']) {
            return response()->json(['message' => $result['message']], 400);
        }

        return response()->json([
            'message' => $result['message'],
            'deleteType' => $result['deleteType']
        ]);
    }

    /**
     * Restore a soft-deleted option
     *
     * POST /api/options/{id}/restore
     */
    public function restoreOption(string $id): JsonResponse
    {
        $currentUserId = Auth::id();
        $result = $this->service->restoreOption($id, $currentUserId);

        return $result['success']
            ? response()->json(['message' => $result['message']])
            : response()->json(['message' => $result['message']], 400);
    }

    /**
     * Check if an option can be permanently deleted
     *
     * GET /api/options/{id}/delete-info
     */
    public function getDeleteInfo(string $id): JsonResponse
    {
        $result = $this->service->checkDeleteEligibility($id);

        return response()->json($result);
    }

    /**
     * Get parent options for dropdown (only those with has_child = true)
     *
     * POST /api/options/parents
     */
    public function getParentOptions(SelectOptionRequest $request): JsonResponse
    {
        $options = $this->service->getParentOptions($request);
        return response()->json($options);
    }
}
