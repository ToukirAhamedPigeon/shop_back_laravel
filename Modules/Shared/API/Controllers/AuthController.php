<?php

namespace Modules\Shared\API\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Shared\Application\Requests\Auth\LoginRequest;
use Modules\Shared\Application\Resources\User\UserResource;
use Modules\Shared\Application\Services\IAuthService;

class AuthController extends Controller
{
    private IAuthService $authService;

    public function __construct(IAuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * POST /auth/login
     * Issue short-lived access token + long-lived refresh token via http-only cookie
     */
    public function login(LoginRequest $request)
    {
        $result = $this->authService->login($request);

        if (!$result) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }
        $secure = app()->environment('production');
        return response()
            ->json([
                'accessToken' => $result->accessToken,
                'refreshTokenExpiry' => $result->refreshTokenExpiry->format('Y-m-d\TH:i:s.u\Z'),
                'user' => $result->toArray()['user'],
            ])
            ->cookie(
        'refreshToken',
                $result->refreshToken,
                60*24*7,
                '/',
                null,
                $secure,
                true,
                false,
                $secure ? 'None' : 'Lax'
            );
    }

    /**
     * POST /auth/refresh
     * Issue new access token using refresh token from http-only cookie
     */
    public function refresh(Request $request)
    {
        $refreshToken = $request->cookie('refreshToken');

        if (!$refreshToken) {
            return response()->json(['message' => 'Refresh token required'], 400);
        }

        $result = $this->authService->refreshToken($refreshToken);

        if (!$result) {
            return response()->json(['message' => 'Invalid or expired refresh token'], 401);
        }

        $secure = app()->environment('production');

        return response()
            ->json([
                'accessToken' => $result->accessToken,
                'refreshTokenExpiry' => $result->refreshTokenExpiry->format('Y-m-d\TH:i:s.u\Z'),
                'user' => $result->toArray()['user'],
            ])
            ->cookie(
        'refreshToken',
                $refreshToken,
                60 * 24 * 7,
                '/',
                null,
                $secure,
                true,
                false,
                $secure ? 'None' : 'Lax'
            );
    }

    /**
     * GET /auth/me
     * Return current authenticated user
     */
    public function me(Request $request)
    {
        $userResource = $this->authService->me();

        if (!$userResource) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        return $userResource;
    }


    /**
     * POST /auth/logout
     * Revoke current refresh token and clear cookie
     */
    public function logout(Request $request)
    {
        $refreshToken = $request->cookie('refreshToken');

        if ($refreshToken) {
            $this->authService->logout($refreshToken);
        }

        return response()
            ->json(['message' => 'Logged out successfully'])
            ->withoutCookie('refreshToken');
    }

    /**
     * POST /auth/logout-all
     * Revoke all refresh tokens for the user
     */
    public function logoutAll(Request $request)
    {
        $user = $request->user();
        if ($user) {
            $this->authService->logoutAllDevices($user->id);
        }

        return response()
            ->json(['message' => 'Logged out from all devices'])
            ->withoutCookie('refreshToken');
    }

    /**
     * POST /auth/logout-others
     * Revoke all refresh tokens except the current one
     */
    public function logoutOthers(Request $request)
    {
        $user = $request->user();
        $currentRefreshToken = $request->cookie('refreshToken');

        if ($user && $currentRefreshToken) {
            $this->authService->logoutOtherDevices($currentRefreshToken, $user->id);
        }

        return response()
            ->json(['message' => 'Logged out from other devices']);
    }
}
