<?php

namespace Modules\Shared\Infrastructure\Helpers;

use Modules\Shared\Domain\Entities\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Log;

class JwtHelper
{
    private string $key;
    private string $issuer;
    private string $audience;
    private int $expiryMinutes;

    public function __construct()
    {
        $this->key = env('JWT_KEY', 'your-secret-key-here-change-in-production');
        $this->issuer = env('JWT_ISSUER', 'shop-back');
        $this->audience = env('JWT_AUDIENCE', 'shop-front');
        $this->expiryMinutes = (int) env('JWT_EXPIRY_MINUTES', 10);
    }

    /**
     * Generate JWT token with user claims and permissions
     */
    public function generateToken(User $user, array $permissions): string
    {
        try {
            $payload = [
                'sub' => $user->id,
                'UserId' => $user->id,
                'unique_name' => $user->username,
                'email' => $user->email,
                'mobile_no' => $user->mobileNo ?? '',
                'permissions' => $permissions,
                'timezone' => $user->timezone,
                'nid' => $user->nid,
                'language' => $user->language,
                'iss' => $this->issuer,
                'aud' => $this->audience,
                'iat' => time(),
                'exp' => time() + ($this->expiryMinutes * 60),
            ];

            Log::info('JWT Payload: ' . json_encode($payload));

            return JWT::encode($payload, $this->key, 'HS256');

        } catch (\Exception $e) {
            Log::error('JWT Generation Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Validate and decode JWT token
     */
    public function validateToken(string $token): ?object
    {
        try {
            return JWT::decode($token, new Key($this->key, 'HS256'));
        } catch (\Exception $e) {
            Log::warning('JWT validation failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Load environment variables from .env file (matching .NET behavior)
     */
    public static function loadEnv(): void
    {
        // In Laravel, env is already loaded, this method is for compatibility
    }
}
