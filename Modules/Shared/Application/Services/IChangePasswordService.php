<?php

namespace Modules\Shared\Application\Services;

use Modules\Shared\Application\Requests\User\ChangePasswordRequest;
use Modules\Shared\Application\Requests\User\VerifyPasswordChangeRequest;
use Modules\Shared\Application\Resources\User\ChangePasswordResource;

interface IChangePasswordService
{
    public function requestChangePassword(string $userId, ChangePasswordRequest $request): ChangePasswordResource;

    public function requestChangePasswordAsync(string $userId, ChangePasswordRequest $request): ChangePasswordResource;

    public function validateChangeToken(string $token): bool;

    public function validateChangeTokenAsync(string $token): bool;

    public function completeChangePassword(VerifyPasswordChangeRequest $request): void;

    public function completeChangePasswordAsync(VerifyPasswordChangeRequest $request): void;
}
