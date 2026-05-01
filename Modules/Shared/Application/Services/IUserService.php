<?php

namespace Modules\Shared\Application\Services;

use Modules\Shared\Application\Requests\User\UserFilterRequest;
use Modules\Shared\Application\Requests\User\CreateUserRequest;
use Modules\Shared\Application\Requests\User\UpdateUserRequest;
use Modules\Shared\Application\Requests\User\UpdateProfileRequest;
use Modules\Shared\Application\Requests\User\DeleteUserRequest;
use Modules\Shared\Application\Requests\User\VerifyPasswordChangeRequest;
use Modules\Shared\Application\Requests\User\ChangePasswordRequest;

interface IUserService
{
    public function getUsers(UserFilterRequest $request): array;

    public function getUsersAsync(UserFilterRequest $request): array;

    public function getUser(string $id): ?array;

    public function getUserAsync(string $id): ?array;

    public function getUserForEdit(string $id): ?array;

    public function getUserForEditAsync(string $id): ?array;

    public function createUser(CreateUserRequest $request, ?string $createdBy): array;

    public function createUserAsync(CreateUserRequest $request, ?string $createdBy): array;

    public function regenerateQr(string $id, ?string $currentUserId): ?array;

    public function regenerateQrAsync(string $id, ?string $currentUserId): ?array;

    public function updateUser(string $id, UpdateUserRequest $request, ?string $currentUserId): array;

    public function updateUserAsync(string $id, UpdateUserRequest $request, ?string $currentUserId): array;

    public function getProfile(string $userId): ?array;

    public function getProfileAsync(string $userId): ?array;

    public function updateProfile(string $userId, UpdateProfileRequest $request): array;

    public function updateProfileAsync(string $userId, UpdateProfileRequest $request): array;

    public function requestPasswordChange(string $userId, ChangePasswordRequest $request): array;

    public function requestPasswordChangeAsync(string $userId, ChangePasswordRequest $request): array;

    public function verifyPasswordChange(string $token): array;

    public function verifyPasswordChangeAsync(string $token): array;

    public function deleteUser(string $id, bool $permanent, ?string $currentUserId): array;

    public function deleteUserAsync(string $id, bool $permanent, ?string $currentUserId): array;

    public function restoreUser(string $id, ?string $currentUserId): array;

    public function restoreUserAsync(string $id, ?string $currentUserId): array;

    public function checkDeleteEligibility(string $id): array;

    public function checkDeleteEligibilityAsync(string $id): array;
    public function bulkDelete(array $ids, bool $permanent, ?string $currentUserId): array;
    public function bulkRestore(array $ids, ?string $currentUserId): array;
}
