<?php

namespace Modules\Shared\Application\Resources\Auth;

class ValidateResetTokenResource
{
    public bool $isValid;
    public ?string $reason;
    public ?string $userId; // UUID in string format

    public function __construct(bool $isValid, ?string $reason = null, ?string $userId = null)
    {
        $this->isValid = $isValid;
        $this->reason = $reason;
        $this->userId = $userId;
    }

    public function toArray(): array
    {
        return [
            'isValid' => $this->isValid,
            'reason'  => $this->reason,
            'userId'  => $this->userId,
        ];
    }
}
