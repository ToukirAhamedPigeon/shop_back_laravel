<?php

namespace Modules\Shared\Application\Services;

use Modules\Shared\Application\Requests\Auth\LoginRequest;
use Modules\Shared\Application\Resources\Auth\AuthResource;

interface IAuthService
{
    public function login(LoginRequest $request): ?AuthResource;

    public function loginAsync(LoginRequest $request): ?AuthResource;

    public function refreshToken(string $token): ?AuthResource;

    public function refreshTokenAsync(string $token): ?AuthResource;

    public function logout(string $token): void;

    public function logoutAsync(string $token): void;

    public function logoutAllDevices(string $userId): void;

    public function logoutAllDevicesAsync(string $userId): void;

    public function logoutOtherDevices(string $exceptRefreshToken, string $userId): void;

    public function logoutOtherDevicesAsync(string $exceptRefreshToken, string $userId): void;
}
