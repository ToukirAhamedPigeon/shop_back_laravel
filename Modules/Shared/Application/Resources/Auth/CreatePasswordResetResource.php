<?php

namespace Modules\Shared\Application\Resources\Auth;

class CreatePasswordResetResource
{
    public string $message;

    public function __construct(string $message)
    {
        $this->message = $message;
    }

    public function toArray(): array
    {
        return [
            'message' => $this->message,
        ];
    }
}
