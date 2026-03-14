<?php

namespace Modules\Shared\API\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Shared\Application\Services\IPasswordResetService;
use Modules\Shared\Application\Services\IChangePasswordService;
use Modules\Shared\Application\Requests\Auth\ResetPasswordRequest;
use Modules\Shared\Application\Requests\User\ChangePasswordRequest;
use Modules\Shared\Application\Requests\User\VerifyPasswordChangeRequest;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PasswordResetController extends Controller
{
    private IPasswordResetService $resetService;
    private IChangePasswordService $changePasswordService;

    public function __construct(
        IPasswordResetService $resetService,
        IChangePasswordService $changePasswordService
    ) {
        $this->resetService = $resetService;
        $this->changePasswordService = $changePasswordService;
    }

    /**
     * 1️⃣ Request Password Reset Email (Public)
     *
     * POST /api/auth/password-reset/request
     */
    public function request(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        try {
            $this->resetService->requestPasswordReset($request->input('email'));

            return response()->json([
                'success' => true,
                'message' => 'Password reset email sent.'
            ]);
        } catch (Exception $ex) {
            Log::error('Error in requestPasswordReset: ' . $ex->getMessage());
            return response()->json([
                'success' => false,
                'message' => $ex->getMessage()
            ], 400);
        }
    }

   /**
     * 2️⃣ Validate Reset Token (Public)
     *
     * GET /api/auth/password-reset/validate/{token}
     */
    public function validateToken(string $token): JsonResponse
    {
        try {
            $isValid = $this->resetService->validateToken($token);

            if (!$isValid) {
                return response()->json([
                    'success' => false,
                    'isValid' => false,
                    'message' => 'Invalid or expired token'
                ], 400);
            }

            return response()->json([
                'success' => true,
                'isValid' => true,
                'message' => 'Token is valid'
            ]);
        } catch (Exception $ex) {
            Log::error('Error in validateToken: ' . $ex->getMessage());
            return response()->json([
                'success' => false,
                'isValid' => false,
                'message' => $ex->getMessage()
            ], 400);
        }
    }

    /**
     * 3️⃣ Reset Password (Public)
     *
     * POST /api/auth/password-reset/reset
     */
    public function reset(ResetPasswordRequest $request): JsonResponse
    {
        try {
            $this->resetService->resetPassword($request);

            return response()->json([
                'success' => true,
                'message' => 'Password successfully reset.'
            ]);
        } catch (Exception $ex) {
            Log::error('Error in resetPassword: ' . $ex->getMessage());
            return response()->json([
                'success' => false,
                'message' => $ex->getMessage()
            ], 400);
        }
    }

    /**
     * 4️⃣ Request Password Change (Authenticated)
     *
     * POST /api/auth/password-reset/change-password/request
     */
    public function requestPasswordChange(ChangePasswordRequest $request): JsonResponse
    {
        // Get current user ID from JWT token
        $currentUserId = Auth::id();

        if (!$currentUserId) {
            return response()->json(['message' => 'User not authenticated'], 401);
        }

        try {
            $result = $this->changePasswordService->requestChangePassword($currentUserId, $request);

            return response()->json([
                'message' => $result->message,
                'requiresVerification' => $result->requiresVerification
            ]);
        } catch (Exception $ex) {
            Log::error('Error in requestPasswordChange: ' . $ex->getMessage());
            return response()->json(['message' => $ex->getMessage()], 400);
        }
    }

    /**
     * 5️⃣ Verify Password Change (Public - with token)
     *
     * POST /api/auth/password-reset/change-password/verify
     */
    public function verifyPasswordChange(VerifyPasswordChangeRequest $request): JsonResponse
    {
        try {
            $this->changePasswordService->completeChangePassword($request);

            return response()->json([
                'message' => 'Password changed successfully'
            ]);
        } catch (Exception $ex) {
            Log::error('Error in verifyPasswordChange: ' . $ex->getMessage());
            return response()->json(['message' => $ex->getMessage()], 400);
        }
    }

    /**
     * 6️⃣ Validate Change Token (Public)
     *
     * GET /api/auth/password-reset/change-password/validate/{token}
     */
    public function validateChangeToken(string $token): JsonResponse
    {
        try {
            $isValid = $this->changePasswordService->validateChangeToken($token);

            return response()->json(['isValid' => $isValid]);
        } catch (Exception $ex) {
            Log::error('Error in validateChangeToken: ' . $ex->getMessage());
            return response()->json(['isValid' => false], 400);
        }
    }
}
