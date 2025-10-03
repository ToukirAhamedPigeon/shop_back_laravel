<?php

namespace Modules\Shared\Application\Repositories;

use Modules\Shared\Domain\Entities\User;

interface IUserRepository
{
    public function getByIdentifier(string $identifier): ?User;
    public function findById(string $id): ?User;
    public function findByEmail(string $email): ?User;
    public function findByUsername(string $username): ?User;
    public function create(User $user): User;
    public function update(User $user): User;
    public function delete(User $user): void;

    /**
     * DDD-friendly method to generate an access token using Laravel Sanctum.
     */
    public function createAccessToken(User $user, string $tokenName = 'API Token'): string;
    public function revokeAllAccessTokens(string $userId): void;
    public function revokeOtherAccessTokens(string $userId, string $exceptToken): void;
}
