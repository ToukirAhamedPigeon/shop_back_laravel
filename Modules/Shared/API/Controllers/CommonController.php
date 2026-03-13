<?php

namespace Modules\Shared\API\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Shared\Application\Services\IUniqueCheckService;
use Modules\Shared\Application\Requests\Common\CheckUniqueRequest;
use Illuminate\Support\Facades\Log;

class CommonController extends Controller
{
    private IUniqueCheckService $service;

    public function __construct(IUniqueCheckService $service)
    {
        $this->service = $service;
    }

    /**
     * Check if a value is unique in a specified field.
     *
     * POST /api/common/check-unique
     *
     * @param CheckUniqueRequest $request
     * @return JsonResponse
     */
    public function checkUnique(CheckUniqueRequest $request): JsonResponse
    {
        try {
            $exists = $this->service->exists($request);

            return response()->json([
                'exists' => $exists
            ]);

        } catch (\InvalidArgumentException $e) {
            // Handle validation errors from the service
            return response()->json([
                'error' => $e->getMessage()
            ], 400);

        } catch (\Exception $e) {
            // Log unexpected errors
            Log::error('Check unique error: ' . $e->getMessage(), [
                'request' => $request->all()
            ]);

            return response()->json([
                'error' => 'An error occurred while checking uniqueness.'
            ], 500);
        }
    }

    /**
     * Async version for interface compatibility
     */
    public function checkUniqueAsync(CheckUniqueRequest $request): JsonResponse
    {
        return $this->checkUnique($request);
    }
}
