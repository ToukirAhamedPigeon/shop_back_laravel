<?php

namespace Modules\Shared\Application\Repositories;

use Modules\Shared\Domain\Entities\User;
use Modules\Shared\Application\Requests\User\UserFilterRequest;
use Modules\Shared\Application\Requests\Common\SelectOptionRequest;

interface IUserRepository
{
    // Existence checks
    public function existsByUsername(string $username, ?string $ignoreId = null): bool;
    public function existsByEmail(string $email, ?string $ignoreId = null): bool;
    public function existsByMobileNo(string $mobileNo, ?string $ignoreId = null): bool;
    public function existsByNID(string $nid, ?string $ignoreId = null): bool;

    // Find methods
    public function getByIdentifier(string $identifier): ?User;
    public function getByEmail(string $email): ?User;
    public function getById(string $id): ?User;
    public function getByMobileNo(string $mobileNo): ?User;

    // Filtered list with pagination
    public function getFiltered(UserFilterRequest $req): array;

    // CRUD operations
    public function add(User $user): User;
    public function update(User $user): User;
    public function saveChanges(): void;

    // Async versions for interface compatibility
    public function getByIdentifierAsync(string $identifier): ?User;
    public function getByEmailAsync(string $email): ?User;
    public function getByIdAsync(string $id): ?User;
    public function getByMobileNoAsync(string $mobileNo): ?User;
    public function getFilteredAsync(UserFilterRequest $req): array;
    public function addAsync(User $user): User;
    public function updateAsync(User $user): User;
    public function saveChangesAsync(): void;

    // Access token management
    public function createAccessToken(User $user, string $tokenName = 'API Token'): string;
    public function revokeAllAccessTokens(string $userId): void;
    public function revokeOtherAccessTokens(string $userId, string $exceptTokenId): void;

    // Related records check
    public function hasRelatedRecords(string $userId): bool;
    public function hasVerifiedEmail(string $userId): bool;

    // Delete operations
    public function hardDelete(string $userId): void;
    public function softDelete(string $userId, ?string $deletedBy = null): void;
    public function restore(string $userId, ?string $restoredBy = null): void;

    // Select options for dropdowns
    public function getDistinctCreators(SelectOptionRequest $req): array;
    public function getDistinctUpdaters(SelectOptionRequest $req): array;
    public function getDistinctDateTypes(SelectOptionRequest $req): array;

    // Async versions for select options
    public function getDistinctCreatorsAsync(SelectOptionRequest $req): array;
    public function getDistinctUpdatersAsync(SelectOptionRequest $req): array;
    public function getDistinctDateTypesAsync(SelectOptionRequest $req): array;
}
