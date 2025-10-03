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
use Carbon\Carbon;

class AuthService implements IAuthService
{
    private IUserRepository $userRepo;
    private IRefreshTokenRepository $refreshTokenRepo;

    public function __construct(IUserRepository $userRepo, IRefreshTokenRepository $refreshTokenRepo)
    {
        $this->userRepo = $userRepo;
        $this->refreshTokenRepo = $refreshTokenRepo;
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

        // 1️⃣ Short-lived Passport access token (default 15 min)
        $accessToken = $this->userRepo->createAccessToken($user, 'API Token');

        // 2️⃣ Long-lived refresh token (7 days)
        $refreshToken = new RefreshToken(
            id: Str::uuid()->toString(),
            token: Str::uuid()->toString(),
            expiresAt: Carbon::now()->addDays(7)->toDateTimeImmutable(),
            userId: $user->id
        );

        $this->refreshTokenRepo->create($refreshToken);
        $this->refreshTokenRepo->removeExpired();

        return new AuthResource($accessToken, $refreshToken->token, $user, $refreshToken->expiresAt);
    }

    /**
     * Get authenticated user from access token
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
        $newRefreshToken = new RefreshToken(
            id: Str::uuid()->toString(),
            token: Str::uuid()->toString(),
            expiresAt: Carbon::now()->addDays(7)->toDateTimeImmutable(),
            userId: $user->id
        );

        $this->refreshTokenRepo->create($newRefreshToken);
        $this->refreshTokenRepo->revoke($refreshToken);

        return new AuthResource($accessToken, $newRefreshToken->token, $user, $newRefreshToken->expiresAt);
    }

    /**
     * Logout (revoke single refresh token + access token)
     */
    public function logout(string $refreshTokenString): void
    {
        $refreshToken = $this->refreshTokenRepo->findValidToken($refreshTokenString);
        if ($refreshToken) {
            $this->refreshTokenRepo->revoke($refreshToken);
        }

        $userId = $refreshToken?->userId;
        if ($userId) {
            $this->userRepo->revokeOtherAccessTokens($userId, ''); // revoke current token only
        }
    }

    /**
     * Logout all sessions
     */
    public function logoutAllDevices(string $userId): void
    {
        $this->refreshTokenRepo->revokeAll($userId);
        $this->userRepo->revokeAllAccessTokens($userId);
    }

    /**
     * Logout other sessions except one refresh token
     */
    public function logoutOtherDevices(string $exceptRefreshToken, string $userId): void
    {
        $this->refreshTokenRepo->revokeOther($exceptRefreshToken, $userId);
    }
}
