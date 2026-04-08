<?php

namespace Modules\Shared\API\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Shared\Application\Requests\Translation\TranslationFilterRequest;
use Modules\Shared\Application\Requests\Translation\CreateTranslationRequest;
use Modules\Shared\Application\Requests\Translation\UpdateTranslationRequest;
use Modules\Shared\Application\Services\ITranslationService;
use Modules\Shared\Application\Repositories\IRolePermissionRepository;
use Illuminate\Support\Facades\Auth;

class TranslationsController extends Controller
{
    private ITranslationService $translationService;
    private IRolePermissionRepository $rolePermissionRepo;

    public function __construct(
        ITranslationService $translationService,
        IRolePermissionRepository $rolePermissionRepo
    ) {
        $this->translationService = $translationService;
        $this->rolePermissionRepo = $rolePermissionRepo;
    }

    /**
     * GET /api/translations/get
     * Get translations for a given language and module.
     * Pass forceFetch=true to bypass cache (e.g., on frontend language switch).
     */
    public function get(Request $request): JsonResponse
    {
        $lang = $request->query('lang', 'en');
        $module = $request->query('module', null);
        $forceFetch = filter_var($request->query('forceFetch', false), FILTER_VALIDATE_BOOLEAN);

        $translations = $this->translationService->getTranslations($lang, $module, $forceFetch);

        return response()->json([
            'lang' => $lang,
            'module' => $module,
            'translations' => $translations,
        ]);
    }

    /**
     * POST /api/translations/list
     * Get paginated list of translations with filtering
     */
    public function getTranslations(TranslationFilterRequest $request): JsonResponse
    {
        $result = $this->translationService->getTranslationsPaginated($request);
        return response()->json($result);
    }

    /**
     * GET /api/translations/{id}
     * Get a single translation by ID
     */
    public function getTranslation(int $id): JsonResponse
    {
        $translation = $this->translationService->getTranslationById($id);
        if (!$translation) {
            return response()->json(['message' => 'Translation not found'], 404);
        }
        return response()->json($translation);
    }

    /**
     * GET /api/translations/{id}/edit
     * Get translation for editing
     */
    public function getTranslationForEdit(int $id): JsonResponse
    {
        $translation = $this->translationService->getTranslationForEdit($id);
        if (!$translation) {
            return response()->json(['message' => 'Translation not found'], 404);
        }
        return response()->json($translation);
    }

    /**
     * POST /api/translations/create
     * Create a new translation
     */
    public function createTranslation(CreateTranslationRequest $request): JsonResponse
    {
        $currentUserId = Auth::id();
        $result = $this->translationService->createTranslation($request, $currentUserId);

        if (!$result['success']) {
            return response()->json(['message' => $result['message']], 400);
        }

        return response()->json(['message' => $result['message']]);
    }

    /**
     * PUT /api/translations/{id}
     * Update an existing translation
     */
    public function updateTranslation(int $id, UpdateTranslationRequest $request): JsonResponse
    {
        $currentUserId = Auth::id();

        // Check if user has Developer role using the role permission repository
        $user = Auth::user();
        $isDeveloper = false;

        if ($user && $user->id) {
            // Use the role permission repository to get roles
            $roles = $this->rolePermissionRepo->getRoleNamesByUserId($user->id);
            $isDeveloper = in_array('developer', $roles);
        }

        $result = $this->translationService->updateTranslation($id, $request, $currentUserId, $isDeveloper);

        if (!$result['success']) {
            return response()->json(['message' => $result['message']], 400);
        }

        return response()->json(['message' => $result['message']]);
    }

    /**
     * DELETE /api/translations/{id}
     * Delete a translation
     */
    public function deleteTranslation(int $id): JsonResponse
    {
        $currentUserId = Auth::id();
        $result = $this->translationService->deleteTranslation($id, $currentUserId);

        if (!$result['success']) {
            return response()->json(['message' => $result['message']], 400);
        }

        return response()->json(['message' => $result['message']]);
    }

    /**
     * GET /api/translations/modules
     * Get distinct modules for filtering options
     */
    public function getModules(): JsonResponse
    {
        $modules = $this->translationService->getModulesForOptions();
        return response()->json($modules);
    }
}
