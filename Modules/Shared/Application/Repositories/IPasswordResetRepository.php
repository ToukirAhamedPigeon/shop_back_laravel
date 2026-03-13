<?php

namespace Modules\Shared\Application\Repositories;

use Modules\Shared\Domain\Entities\PasswordReset;

interface IPasswordResetRepository
{
    public function getByToken(string $token, string $tokenType = 'reset'): ?PasswordReset;

    public function getByTokenAsync(string $token, string $tokenType = 'reset'): ?PasswordReset;

    public function getAllByUser(string $userId, string $tokenType): array;

    public function getAllByUserAsync(string $userId, string $tokenType): array;

    public function add(PasswordReset $passwordReset): PasswordReset;

    public function addAsync(PasswordReset $passwordReset): PasswordReset;

    public function update(PasswordReset $passwordReset): PasswordReset;

    public function updateAsync(PasswordReset $passwordReset): PasswordReset;

    public function saveChanges(): void;

    public function saveChangesAsync(): void;

    public function markExistingTokensAsUsed(string $userId, string $tokenType): void;

    public function markExistingTokensAsUsedAsync(string $userId, string $tokenType): void;

    public function markUsed(PasswordReset $passwordReset): void;

    public function deleteExpired(): int;
}
