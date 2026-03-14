<?php

namespace Modules\Shared\API\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Shared\Application\Services\IUserService;
use Modules\Shared\Application\Services\IMailVerificationService;
use Modules\Shared\Application\Requests\User\UserFilterRequest;
use Modules\Shared\Application\Requests\User\CreateUserRequest;
use Modules\Shared\Application\Requests\User\UpdateUserRequest;
use Modules\Shared\Application\Requests\User\UpdateProfileRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class UserController extends Controller
{
    private IUserService $service;
    private IMailVerificationService $mailVerificationService;

    public function __construct(
        IUserService $service,
        IMailVerificationService $mailVerificationService
    ) {
        $this->service = $service;
        $this->mailVerificationService = $mailVerificationService;
    }

    /**
     * Get paginated list of users
     *
     * POST /api/users
     */
    public function getUsers(UserFilterRequest $request): JsonResponse
    {
        $result = $this->service->getUsers($request);
        return response()->json($result);
    }

    /**
     * Get user by ID
     *
     * GET /api/users/{id}
     */
    public function getUser(string $id): JsonResponse
    {
        $user = $this->service->getUser($id);
        if (!$user) {
            return response()->json(null, 404);
        }
        return response()->json($user);
    }

    /**
     * Create new user
     *
     * POST /api/users/create
     */
    public function create(CreateUserRequest $request): JsonResponse
    {
        $currentUserId = Auth::id();
        $result = $this->service->createUser($request, $currentUserId);

        return $result['success']
            ? response()->json($result)
            : response()->json($result, 400);
    }

   /**
     * Verify email with token
     *
     * GET /api/users/verify-email
     */
    public function verifyEmail(Request $request): JsonResponse
    {
        $token = $request->query('token');
        if (!$token) {
            return response()->json(['message' => 'Token is required'], 400);
        }

        $result = $this->mailVerificationService->verifyToken($token);

        if (!$result['success']) {
            // .NET returns just the message string on error
            return response()->json($result['message'], 400);
        }

        // .NET returns just the message string on success
        return response()->json($result['message']);
    }

    /**
     * Resend verification email
     *
     * POST /api/users/{id}/resend-verification
     */
    public function resendVerification(string $id): JsonResponse
    {
        $result = $this->mailVerificationService->resendVerification($id);

        if (!$result['success']) {
            // .NET returns just the message string on error
            return response()->json($result['message'], 400);
        }

        // .NET returns just the message string on success
        return response()->json($result['message']);
    }

    /**
     * Regenerate QR code for user
     *
     * POST /api/users/{id}/regenerate-qr
     */
    public function regenerateQr(string $id): JsonResponse
    {
        $currentUserId = Auth::id();
        $user = $this->service->regenerateQr($id, $currentUserId);

        if (!$user) {
            return response()->json(null, 404);
        }

        return response()->json($user);
    }

    /**
     * Get user for edit (separates direct permissions)
     *
     * GET /api/users/{id}/edit
     */
    public function getUserForEdit(string $id): JsonResponse
    {
        $user = $this->service->getUserForEdit($id);
        if (!$user) {
            return response()->json(null, 404);
        }

        return response()->json($user);
    }

    /**
     * Update user
     *
     * PUT /api/users/{id}
     */
    public function update(string $id, UpdateUserRequest $request): JsonResponse
    {
        $currentUserId = Auth::id();
        $result = $this->service->updateUser($id, $request, $currentUserId);

        return $result['success']
            ? response()->json($result)
            : response()->json(['message' => $result['message']], 400);
    }

    /**
     * Get current user profile
     *
     * GET /api/users/profile
     */
    public function getProfile(): JsonResponse
    {
        $currentUserId = Auth::id();

        if (!$currentUserId || !Str::isUuid($currentUserId)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $user = $this->service->getProfile($currentUserId);
        if (!$user) {
            return response()->json(null, 404);
        }

        return response()->json($user);
    }

    /**
     * Update current user profile
     *
     * PUT /api/users/profile
     */
    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $currentUserId = Auth::id();

        if (!$currentUserId || !Str::isUuid($currentUserId)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $result = $this->service->updateProfile($currentUserId, $request);

        return $result['success']
            ? response()->json($result)
            : response()->json(['message' => $result['message']], 400);
    }

    /**
     * Delete user (soft or hard)
     *
     * DELETE /api/users/{id}?permanent=false
     */
    public function deleteUser(string $id, Request $request): JsonResponse
    {
        $permanent = filter_var($request->query('permanent', 'false'), FILTER_VALIDATE_BOOLEAN);
        $currentUserId = Auth::id();

        $result = $this->service->deleteUser($id, $permanent, $currentUserId);

        if (!$result['success']) {
            return response()->json(['message' => $result['message']], 400);
        }

        return response()->json([
            'message' => $result['message'],
            'deleteType' => $result['deleteType']
        ]);
    }

    /**
     * Restore soft-deleted user
     *
     * POST /api/users/{id}/restore
     */
    public function restoreUser(string $id): JsonResponse
    {
        $currentUserId = Auth::id();
        $result = $this->service->restoreUser($id, $currentUserId);

        return $result['success']
            ? response()->json(['message' => $result['message']])
            : response()->json(['message' => $result['message']], 400);
    }

    /**
     * Get delete eligibility info
     *
     * GET /api/users/{id}/delete-info
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
