<?php

namespace Modules\Shared\API\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Shared\Application\Requests\Auth\LoginRequest;
use Modules\Shared\Application\Services\IAuthService;
use Illuminate\Support\Facades\App;
use Exception;
use Illuminate\Support\Facades\Log;


class AuthController extends Controller
{
    private IAuthService $authService;

    public function __construct(IAuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * POST /api/auth/login
     * Authenticates a user with credentials and issues JWT + Refresh Token in HttpOnly cookie
     */
    public function login(LoginRequest $request)
    {
        try {
            $result = $this->authService->login($request);

            // FIX: Don't try to log the entire array in string concatenation
            Log::info('Login result received: ' . ($result ? 'yes' : 'no'));

            if (!$result) {
                return response()->json(['message' => 'Invalid credentials'], 401);
            }

            $expiry = now()->addDays(7); // 7 days expiry for cookie

            // Create response with JSON data
            $response = response()->json([
                'user' => $result->toArray()['user'],
                'accessToken' => $result->accessToken,
                'refreshTokenExpiry' => $expiry->format('Y-m-d\TH:i:s.u') . '0Z'
            ]);

            // Append HttpOnly cookie with refresh token
            return $this->appendRefreshTokenCookie(
                $response,
                $result->refreshToken,
                $expiry
            );

        } catch (Exception $ex) {
            $message = $ex->getMessage();

            if ($message === 'EMAIL_NOT_VERIFIED') {
                return response()->json(['message' => 'EMAIL_NOT_VERIFIED'], 401);
            }

            if ($message === 'USER_INACTIVE') {
                return response()->json(['message' => 'USER_INACTIVE'], 401);
            }

            return response()->json(['message' => 'Invalid credentials'], 401);
        }
    }

    /**
     * POST /api/auth/refresh
     * Issues a new access token using the refresh token stored in cookie
     */
    public function refresh(Request $request)
    {
        $refreshToken = $request->cookie('refreshToken');

        if (empty($refreshToken)) {
            return response()->json(['message' => 'Missing refresh token'], 401);
        }

        $result = $this->authService->refreshToken($refreshToken);

        if (!$result) {
            return response()->json(['message' => 'Invalid refresh token'], 401);
        }

        if (!$result->user->isActive) {
            return response()->json(['message' => 'User is inactive'], 401);
        }

        $expiry = now()->addDays(7);

        $response = response()->json([
            'user' => $result->toArray()['user'],
            'accessToken' => $result->accessToken,
            'refreshTokenExpiry' => $expiry->format('Y-m-d\TH:i:s.u') . '0Z'
        ]);

        // Keep the same refresh token in cookie
        return $this->appendRefreshTokenCookie(
            $response,
            $refreshToken,
            $expiry
        );
    }

    /**
     * POST /api/auth/logout
     * Logs out the current device (revokes the refresh token in cookie)
     */
    public function logout(Request $request)
    {
        $refreshToken = $request->cookie('refreshToken');

        if ($refreshToken) {
            $this->authService->logout($refreshToken);
        }

        return response()
            ->json(['message' => 'Logged out successfully'])
            ->withoutCookie('refreshToken', '/');
    }

    /**
     * POST /api/auth/logout-all
     * Logs out user from all devices (revokes all refresh tokens)
     */
    public function logoutAll(Request $request)
    {
        $user = $request->user();

        if ($user) {
            $this->authService->logoutAllDevices($user->id);
        }

        return response()
            ->json(['message' => 'Logged out from all devices'])
            ->withoutCookie('refreshToken', '/');
    }

    /**
     * POST /api/auth/logout-others
     * Logs out from all other devices except the current one
     */
    public function logoutOthers(Request $request)
    {
        $refreshToken = $request->cookie('refreshToken');

        if ($refreshToken) {
            $user = $request->user();
            if ($user) {
                $this->authService->logoutOtherDevices($refreshToken, $user->id);
            }
        }

        return response()->json(['message' => 'Logged out from other devices']);
    }

    /**
     * GET /api/auth/me
     * Returns the current authenticated user (kept for compatibility)
     */
    public function me(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        return response()->json(['user' => $user]);
    }

    /**
     * Private helper to append refresh token cookie with proper settings
     */
    private function appendRefreshTokenCookie($response, string $token, \DateTime $expiry)
    {
        $secure = App::environment('production');

        return $response->cookie(
            'refreshToken',           // name
            $token,                    // value
            60 * 24 * 7,               // minutes (7 days)
            '/',                       // path
            null,                      // domain
            $secure,                   // secure (HTTPS only)
            true,                      // httpOnly
            false,                     // raw
            $secure ? 'None' : 'Lax'   // sameSite
        );
    }
}
