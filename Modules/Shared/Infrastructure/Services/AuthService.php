<?php

namespace Modules\Shared\Infrastructure\Services;

use Illuminate\Support\Str;
use Modules\Shared\Application\Requests\Auth\LoginRequest;
use Modules\Shared\Application\Services\IAuthService;
use Modules\Shared\Application\Resources\Auth\AuthResource;
use Modules\Shared\Application\Resources\Auth\UserResource;
use Modules\Shared\Domain\Entities\RefreshToken;
use Modules\Shared\Application\Repositories\IUserRepository;
use Modules\Shared\Application\Repositories\IRefreshTokenRepository;
use Modules\Shared\Infrastructure\Helpers\UserLogHelper;
use Carbon\Carbon;

class AuthService implements IAuthService
{
    private IUserRepository $userRepo;
    private IRefreshTokenRepository $refreshTokenRepo;
    private UserLogHelper $userLogHelper;

    public function __construct(
        IUserRepository $userRepo,
        IRefreshTokenRepository $refreshTokenRepo,
        UserLogHelper $userLogHelper
    ) {
        $this->userRepo = $userRepo;
        $this->refreshTokenRepo = $refreshTokenRepo;
        $this->userLogHelper = $userLogHelper;
    }

    /**
     * Login and issue access + refresh token
     */
    public function login(LoginRequest $request): ?AuthResource
    {
        $user = $this->userRepo->getByIdentifier($request->identifier);

        if (!$user || !password_verify($request->password, $user->password)) {
            return null;
        }
        $user->updateLastLogin($this->userLogHelper->getClientIp());
        $this->userRepo->update($user);

        // Access token (short-lived)
        $accessToken = $this->userRepo->createAccessToken($user, 'API Token');

        // Refresh token (long-lived)
        $refreshToken = new RefreshToken(
            id: Str::uuid()->toString(),
            token: Str::uuid()->toString(),
            expiresAt: Carbon::now()->addDays(7)->toDateTimeImmutable(),
            userId: $user->id
        );
        $this->refreshTokenRepo->create($refreshToken);
        $this->refreshTokenRepo->removeExpired();

        // ✅ Log login with explicit userId
        $this->userLogHelper->log(
            actionType: 'Login',
            detail: "User '{$user->name}' logged in successfully.",
            modelName: 'User',
            modelId: $user->id,
            userId: $user->id // Explicitly pass user ID
        );

        return new AuthResource($accessToken, $refreshToken->token, $user, $refreshToken->expiresAt);
    }

    /**
     * Get authenticated user
     */
    public function me(): ?UserResource
    {
        $userId = auth('api')->id();
        if (!$userId) return null;

        $domainUser = $this->userRepo->findById($userId);
        return $domainUser ? new UserResource($domainUser) : null;
    }

    /**
     * Refresh access token using refresh token
     */
    public function refreshToken(string $token): ?AuthResource
    {
        $refreshToken = $this->refreshTokenRepo->findValidToken($token);
        if (!$refreshToken) return null;

        $user = $this->userRepo->findById($refreshToken->userId);
        if (!$user || !$user->isActive) return null;

        $accessToken = $this->userRepo->createAccessToken($user, 'API Token');

        // Issue new refresh token
        // $newRefreshToken = new RefreshToken(
        //     id: Str::uuid()->toString(),
        //     token: Str::uuid()->toString(),
        //     expiresAt: Carbon::now()->addDays(7)->toDateTimeImmutable(),
        //     userId: $user->id
        // );

        // $this->refreshTokenRepo->create($newRefreshToken);
        // $this->refreshTokenRepo->revoke($refreshToken);

        // // ✅ Log refresh token with explicit userId
        $this->userLogHelper->log(
            actionType: 'RefreshToken',
            detail: "User '{$user->name}' refreshed access token.",
            modelName: 'User',
            modelId: $user->id,
            userId: $user->id
        );

        return new AuthResource($accessToken, $refreshToken->token, $user, $refreshToken->expiresAt);
    }

    /**
     * Logout (revoke single refresh token)
     */
    public function logout(string $refreshTokenString): void
    {
        $refreshToken = $this->refreshTokenRepo->findValidToken($refreshTokenString);
        if ($refreshToken) {
            $this->refreshTokenRepo->revoke($refreshToken);

            $userId = $refreshToken->userId;
            $user = $this->userRepo->findById($userId);

            // ✅ Log logout with explicit userId
            $this->userLogHelper->log(
                actionType: 'Logout',
                detail: "User '{$user?->name}' logged out successfully.",
                modelName: 'User',
                modelId: $userId,
                userId: $userId
            );

            $this->userRepo->revokeOtherAccessTokens($userId, '');
        }
    }

    /**
     * Logout all sessions
     */
    public function logoutAllDevices(string $userId): void
    {
        $user = $this->userRepo->findById($userId);

        $this->refreshTokenRepo->revokeAll($userId);
        $this->userRepo->revokeAllAccessTokens($userId);

        // ✅ Log logout all devices with explicit userId
        $this->userLogHelper->log(
            actionType: 'LogoutAllDevices',
            detail: "User '{$user?->name}' logged out from all devices.",
            modelName: 'User',
            modelId: $userId,
            userId: $userId
        );
    }

    /**
     * Logout other sessions except one refresh token
     */
    public function logoutOtherDevices(string $exceptRefreshToken, string $userId): void
    {
        $user = $this->userRepo->findById($userId);

        $this->refreshTokenRepo->revokeOther($exceptRefreshToken, $userId);

        // ✅ Log logout other devices with explicit userId
        $this->userLogHelper->log(
            actionType: 'LogoutOtherDevices',
            detail: "User '{$user?->name}' logged out from other devices except current.",
            modelName: 'User',
            modelId: $userId,
            userId: $userId
        );
    }
}
