<?php

namespace Modules\Shared\Application\Resources\Auth;

use Modules\Shared\Domain\Entities\User;

class AuthResource
{
    public string $accessToken;
    public string $refreshToken;
    public User $user;
    public \DateTimeImmutable $refreshTokenExpiry; // ✅ new property

    public function __construct(string $accessToken, string $refreshToken, User $user, \DateTimeImmutable $refreshTokenExpiry)
    {
        $this->accessToken = $accessToken;
        $this->refreshToken = $refreshToken;
        $this->user = $user;
        $this->refreshTokenExpiry = $refreshTokenExpiry; // ✅ assign
    }

    public function toArray(): array
    {
        return [
            'accessToken' => $this->accessToken,
            'refreshToken' => $this->refreshToken,
            'refreshTokenExpiry' => \Carbon\CarbonImmutable::instance($this->refreshTokenExpiry)
                ->format('Y-m-d\TH:i:s.u') . '0Z', // ✅ .NET-style ISO
            'user' => new UserResource($this->user),
        ];
    }
}
