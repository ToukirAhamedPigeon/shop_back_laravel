<?php

namespace Modules\Shared\Application\Resources\User;

class ChangePasswordResource
{
    public function __construct(
        public string $message,
        public bool $requiresVerification = true
    ) {}

    public function toArray(): array
    {
        return [
            'message' => $this->message,
            'requiresVerification' => $this->requiresVerification,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            message: $data['message'] ?? '',
            requiresVerification: $data['requiresVerification'] ?? true
        );
    }
}
