<?php

namespace Modules\Shared\API\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Shared\Application\Services\IPasswordResetService;
use Modules\Shared\Application\Requests\Auth\ResetPasswordRequest;
use Exception;

class PasswordResetController extends Controller
{
    private IPasswordResetService $resetService;

    public function __construct(IPasswordResetService $resetService)
    {
        $this->resetService = $resetService;
    }

    /**
     * 1️⃣ Request Password Reset Email
     */
    public function request(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        try {
            $this->resetService->requestPasswordReset($request->input('email'));

            return response()->json([
                'message' => 'Password reset email sent.'
            ]);
        } catch (Exception $ex) {
            return response()->json([
                'message' => $ex->getMessage()
            ], 400);
        }
    }

    /**
     * 2️⃣ Validate Reset Token
     */
    public function validateToken(string $token): JsonResponse
    {
        try {
            $isValid = $this->resetService->validateToken($token);

            if (!$isValid) {
                return response()->json([
                    'isValid' => false,
                    'reason' => 'Invalid or expired token'
                ], 400);
            }

            return response()->json([
                'isValid' => true
            ]);
        } catch (Exception $ex) {
            return response()->json([
                'isValid' => false,
                'reason' => $ex->getMessage()
            ], 400);
        }
    }

    /**
     * 3️⃣ Reset Password
     */
    public function reset(ResetPasswordRequest $request): JsonResponse
    {
        $request->validate([
            'token' => 'required|string',
            'password' => 'required|string|min:6'
        ]);

        try {
            $this->resetService->resetPassword($request);

            return response()->json([
                'message' => 'Password successfully reset.'
            ]);
        } catch (Exception $ex) {
            return response()->json([
                'message' => $ex->getMessage()
            ], 400);
        }
    }
}
