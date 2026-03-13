<?php

namespace Modules\Shared\API\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Shared\Application\Services\IOptionsService;
use Modules\Shared\Application\Requests\Common\SelectOptionRequest;

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
}
