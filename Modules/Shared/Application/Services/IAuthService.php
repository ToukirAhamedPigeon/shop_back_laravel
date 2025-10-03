<?php

namespace Modules\Shared\Application\Services;

use Modules\Shared\Application\Requests\Auth\LoginRequest;
use Modules\Shared\Application\Resources\Auth\AuthResource;
use Modules\Shared\Application\Resources\Auth\UserResource;

interface IAuthService
{
    public function login(LoginRequest $loginDto): ?AuthResource;

    public function me(): ?UserResource;

    public function refreshToken(string $refreshToken): ?AuthResource;

    public function logout(string $refreshToken): void;

    public function logoutAllDevices(string $userId): void;

    public function logoutOtherDevices(string $exceptRefreshToken, string $userId): void;
}
