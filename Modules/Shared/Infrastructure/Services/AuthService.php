<?php

namespace Modules\Shared\Infrastructure\Services;

use Illuminate\Support\Str;
use Modules\Shared\Application\Requests\Auth\LoginRequest;
use Modules\Shared\Application\Services\IAuthService;
use Modules\Shared\Application\Resources\Auth\AuthResource;
use Modules\Shared\Domain\Entities\RefreshToken;
use Modules\Shared\Application\Repositories\IUserRepository;
use Modules\Shared\Application\Repositories\IRefreshTokenRepository;
use Modules\Shared\Application\Repositories\IRolePermissionRepository;
use Modules\Shared\Infrastructure\Helpers\UserLogHelper;
use Modules\Shared\Infrastructure\Helpers\JwtHelper;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthService implements IAuthService
{
    private IUserRepository $userRepo;
    private IRefreshTokenRepository $refreshTokenRepo;
    private IRolePermissionRepository $rolePermissionRepo;
    private UserLogHelper $userLogHelper;
    private JwtHelper $jwtHelper;

    public function __construct(
        IUserRepository $userRepo,
        IRefreshTokenRepository $refreshTokenRepo,
        IRolePermissionRepository $rolePermissionRepo,
        UserLogHelper $userLogHelper,
        JwtHelper $jwtHelper
    ) {
        $this->userRepo = $userRepo;
        $this->refreshTokenRepo = $refreshTokenRepo;
        $this->rolePermissionRepo = $rolePermissionRepo;
        $this->userLogHelper = $userLogHelper;
        $this->jwtHelper = $jwtHelper;
    }

    /**
     * Login and issue access + refresh token
     */
    public function login(LoginRequest $request): ?AuthResource
    {
        $user = $this->userRepo->getByIdentifier($request->identifier);

        // Check if user exists and password is correct
        if (!$user || !$user->password || !Hash::check($request->password, $user->password)) {
            return null;
        }

        // Check if email is verified
        if ($user->emailVerifiedAt === null) {
            throw new \Exception('EMAIL_NOT_VERIFIED');
        }

        // Check if user is active
        if (!$user->isActive) {
            throw new \Exception('USER_INACTIVE');
        }

        // Update last login info
        $user->updateLastLogin($this->userLogHelper->getClientIp());
        $this->userRepo->update($user);
        $this->userRepo->saveChanges();

        // Get permissions for JWT
        $permissions = $this->rolePermissionRepo->getAllPermissionsByUserId($user->id);

        // Generate JWT access token with permissions
        $accessToken = $this->jwtHelper->generateToken($user, $permissions);

        // Create refresh token
        $refreshToken = new RefreshToken(
            id: (string) Str::uuid(),
            token: (string) Str::uuid(),
            expiresAt: Carbon::now()->addDays(7)->toDateTimeImmutable(),
            userId: $user->id,
            isRevoked: false
        );

        $this->refreshTokenRepo->add($refreshToken);
        $this->refreshTokenRepo->saveChanges();
        $this->refreshTokenRepo->removeExpired();

        // Get roles for user DTO
        $roles = $this->rolePermissionRepo->getRoleNamesByUserId($user->id);

        // Set roles and permissions on user entity for the resource
        $user->roles = $roles;
        $user->permissions = $permissions;

        // Log login
        $this->userLogHelper->log(
            actionType: 'Login',
            detail: "User '{$user->username}' logged in successfully.",
            modelName: 'User',
            modelId: $user->id,
            userId: $user->id
        );

        // ✅ Pass the User entity, not the array
        return new AuthResource(
            accessToken: $accessToken,
            refreshToken: $refreshToken->token,
            user: $user,
            refreshTokenExpiry: $refreshToken->expiresAt
        );
    }

    public function loginAsync(LoginRequest $request): ?AuthResource
    {
        return $this->login($request);
    }

    /**
     * Refresh access token using refresh token
     */
    public function refreshToken(string $token): ?AuthResource
    {
        $existing = $this->refreshTokenRepo->getByToken($token);

        if (!$existing || $existing->isExpired()) {
            return null;
        }

        $this->refreshTokenRepo->removeExpired();

        $user = $this->userRepo->getById($existing->userId);
        if (!$user) {
            return null;
        }

        // Get permissions for JWT
        $permissions = $this->rolePermissionRepo->getAllPermissionsByUserId($user->id);

        // Generate new access token
        $newAccessToken = $this->jwtHelper->generateToken($user, $permissions);

        // Get roles for user DTO
        $roles = $this->rolePermissionRepo->getRoleNamesByUserId($user->id);

        // Set roles and permissions on user entity for the resource
        $user->roles = $roles;
        $user->permissions = $permissions;

        // ✅ Pass the User entity, not the array
        return new AuthResource(
            accessToken: $newAccessToken,
            refreshToken: $token,
            user: $user,
            refreshTokenExpiry: $existing->expiresAt
        );
    }

    public function refreshTokenAsync(string $token): ?AuthResource
    {
        return $this->refreshToken($token);
    }

    /**
     * Logout (revoke single refresh token)
     */
    public function logout(string $token): void
    {
        try {
            $refreshToken = $this->refreshTokenRepo->getByToken($token);

            if ($refreshToken) {
                $this->refreshTokenRepo->revoke($refreshToken);

                // Log logout
                $user = $this->userRepo->getById($refreshToken->userId);
                $this->userLogHelper->log(
                    actionType: 'Logout',
                    detail: "User '{$user?->username}' logged out successfully.",
                    modelName: 'User',
                    modelId: $refreshToken->userId,
                    userId: $refreshToken->userId
                );
            }
        } catch (\Exception $ex) {
            Log::error('Logout Error: ' . $ex->getMessage());
        }
    }

    public function logoutAsync(string $token): void
    {
        $this->logout($token);
    }

    /**
     * Logout all devices
     */
    public function logoutAllDevices(string $userId): void
    {
        try {
            $this->refreshTokenRepo->revokeAll($userId);

            // Also revoke all Passport tokens if needed
            $this->userRepo->revokeAllAccessTokens($userId);

            // Log logout all devices
            $user = $this->userRepo->getById($userId);
            $this->userLogHelper->log(
                actionType: 'LogoutAllDevices',
                detail: "User '{$user?->username}' logged out from all devices.",
                modelName: 'User',
                modelId: $userId,
                userId: $userId
            );
        } catch (\Exception $ex) {
            Log::error('LogoutAllDevices Error: ' . $ex->getMessage());
        }
    }

    public function logoutAllDevicesAsync(string $userId): void
    {
        $this->logoutAllDevices($userId);
    }

    /**
     * Logout other sessions except one refresh token
     */
    public function logoutOtherDevices(string $exceptRefreshToken, string $userId): void
    {
        try {
            $this->refreshTokenRepo->revokeOther($exceptRefreshToken, $userId);

            // Log logout other devices
            $user = $this->userRepo->getById($userId);
            $this->userLogHelper->log(
                actionType: 'LogoutOtherDevices',
                detail: "User '{$user?->username}' logged out from other devices except current.",
                modelName: 'User',
                modelId: $userId,
                userId: $userId
            );
        } catch (\Exception $ex) {
            Log::error('LogoutOtherDevices Error: ' . $ex->getMessage());
        }
    }

    public function logoutOtherDevicesAsync(string $exceptRefreshToken, string $userId): void
    {
        $this->logoutOtherDevices($exceptRefreshToken, $userId);
    }
}
