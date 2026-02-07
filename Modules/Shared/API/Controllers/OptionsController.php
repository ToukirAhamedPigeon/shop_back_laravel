<?php

namespace Modules\Shared\API\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Shared\Application\Services\IOptionsService;
use Modules\Shared\Application\Requests\Common\SelectOptionRequest;

class OptionsController extends Controller
{
    public function __construct(
        private IOptionsService $service
    ) {}

    /**
     * POST /api/Options/{type}
     */
    public function getOptions(string $type, SelectOptionRequest $request): JsonResponse
    {
        return response()->json(
            $this->service->getOptions($type, $request)
        );
    }
}
