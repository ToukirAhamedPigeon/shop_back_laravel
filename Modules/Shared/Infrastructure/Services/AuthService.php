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
use Modules\Shared\Infrastructure\Models\EloquentUser;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Exception;

class AuthService implements IAuthService
{
    private IUserRepository $userRepo;
    private IRefreshTokenRepository $refreshTokenRepo;
    private IRolePermissionRepository $rolePermissionRepo;
    private UserLogHelper $userLogHelper;

    public function __construct(
        IUserRepository $userRepo,
        IRefreshTokenRepository $refreshTokenRepo,
        IRolePermissionRepository $rolePermissionRepo,
        UserLogHelper $userLogHelper
    ) {
        $this->userRepo = $userRepo;
        $this->refreshTokenRepo = $refreshTokenRepo;
        $this->rolePermissionRepo = $rolePermissionRepo;
        $this->userLogHelper = $userLogHelper;
    }

    /**
     * Login and issue access + refresh token using Passport
     */
    public function login(LoginRequest $request): ?AuthResource
    {
        try {
            $user = $this->userRepo->getByIdentifier($request->identifier);

            // Check if user exists and password is correct
            if (!$user || !$user->password || !Hash::check($request->password, $user->password)) {
                return null;
            }

            // Check if email is verified
            if ($user->emailVerifiedAt === null) {
                throw new Exception('EMAIL_NOT_VERIFIED');
            }

            // Check if user is active
            if (!$user->isActive) {
                throw new Exception('USER_INACTIVE');
            }

            // Update last login info
            $clientIp = $this->userLogHelper->getClientIp();
            $user->updateLastLogin($clientIp);
            $this->userRepo->update($user);
            $this->userRepo->saveChanges();

            // Get permissions and roles
            $permissions = $this->rolePermissionRepo->getAllPermissionsByUserId($user->id);
            $roles = $this->rolePermissionRepo->getRoleNamesByUserId($user->id);

            // Set roles and permissions on user entity for the resource
            $user->roles = $roles;
            $user->permissions = $permissions;

            // ===== PASSPORT TOKEN GENERATION =====
            // Get the Eloquent user model for Passport
            $eloquentUser = EloquentUser::find($user->id);

            if (!$eloquentUser) {
                throw new Exception('User model not found');
            }

            // Create Passport access token
            $tokenResult = $eloquentUser->createToken('API Token');
            $accessToken = $tokenResult->accessToken;

            // Create refresh token (custom for your refresh flow)
            $refreshTokenId = (string) Str::uuid();
            $refreshTokenString = (string) Str::uuid();
            $expiresAt = Carbon::now()->addDays(7)->toDateTimeImmutable();

            $refreshToken = new RefreshToken(
                id: $refreshTokenId,
                token: $refreshTokenString,
                expiresAt: $expiresAt,
                userId: $user->id,
                isRevoked: false
            );

            $this->refreshTokenRepo->add($refreshToken);
            $this->refreshTokenRepo->saveChanges();
            $this->refreshTokenRepo->removeExpired();

            // Log login
            $this->userLogHelper->log(
                actionType: 'Login',
                detail: "User '{$user->username}' logged in successfully.",
                modelName: 'User',
                modelId: $user->id,
                userId: $user->id
            );

            // Create and return AuthResource
            return new AuthResource(
                accessToken: $accessToken,
                refreshToken: $refreshToken->token,
                user: $user,
                refreshTokenExpiry: $refreshToken->expiresAt
            );

        } catch (Exception $e) {
            // Re-throw specific exceptions for the controller to handle
            if ($e->getMessage() === 'EMAIL_NOT_VERIFIED' || $e->getMessage() === 'USER_INACTIVE') {
                throw $e;
            }

            // For any other exception, return null (invalid credentials)
            return null;
        }
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
        try {
            $existing = $this->refreshTokenRepo->getByToken($token);

            if (!$existing || $existing->isExpired()) {
                return null;
            }

            $this->refreshTokenRepo->removeExpired();

            $user = $this->userRepo->getById($existing->userId);
            if (!$user) {
                return null;
            }

            // Get permissions and roles
            $permissions = $this->rolePermissionRepo->getAllPermissionsByUserId($user->id);
            $roles = $this->rolePermissionRepo->getRoleNamesByUserId($user->id);

            // Set roles and permissions on user entity for the resource
            $user->roles = $roles;
            $user->permissions = $permissions;

            // Generate new Passport access token
            $eloquentUser = EloquentUser::find($user->id);

            if (!$eloquentUser) {
                throw new Exception('User model not found');
            }

            $tokenResult = $eloquentUser->createToken('API Token');
            $newAccessToken = $tokenResult->accessToken;

            return new AuthResource(
                accessToken: $newAccessToken,
                refreshToken: $token,
                user: $user,
                refreshTokenExpiry: $existing->expiresAt
            );

        } catch (Exception $e) {
            Log::error('Refresh token error: ' . $e->getMessage());
            return null;
        }
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

                // Also revoke Passport tokens for this user
                $user = $this->userRepo->getById($refreshToken->userId);
                if ($user) {
                    $eloquentUser = EloquentUser::find($user->id);
                    if ($eloquentUser) {
                        $eloquentUser->tokens()->delete();
                    }
                }

                // Log logout
                $this->userLogHelper->log(
                    actionType: 'Logout',
                    detail: "User '{$user?->username}' logged out successfully.",
                    modelName: 'User',
                    modelId: $refreshToken->userId,
                    userId: $refreshToken->userId
                );
            }
        } catch (Exception $ex) {
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

            // Revoke all Passport tokens
            $eloquentUser = EloquentUser::find($userId);
            if ($eloquentUser) {
                $eloquentUser->tokens()->delete();
            }

            // Log logout all devices
            $user = $this->userRepo->getById($userId);
            $this->userLogHelper->log(
                actionType: 'LogoutAllDevices',
                detail: "User '{$user?->username}' logged out from all devices.",
                modelName: 'User',
                modelId: $userId,
                userId: $userId
            );
        } catch (Exception $ex) {
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

            // Revoke other Passport tokens (keep current one)
            $eloquentUser = EloquentUser::find($userId);
            if ($eloquentUser) {
                // Get current token from request if needed
                // This requires access to the request, so you might need to pass the token
                // For now, we'll revoke all and let the refresh token flow handle it
                $eloquentUser->tokens()->delete();
            }

            // Log logout other devices
            $user = $this->userRepo->getById($userId);
            $this->userLogHelper->log(
                actionType: 'LogoutOtherDevices',
                detail: "User '{$user?->username}' logged out from other devices except current.",
                modelName: 'User',
                modelId: $userId,
                userId: $userId
            );
        } catch (Exception $ex) {
            Log::error('LogoutOtherDevices Error: ' . $ex->getMessage());
        }
    }

    public function logoutOtherDevicesAsync(string $exceptRefreshToken, string $userId): void
    {
        $this->logoutOtherDevices($exceptRefreshToken, $userId);
    }
}
