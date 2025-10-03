<?php
// Modules/Shared/Application/Requests/Auth/LoginRequest.php

namespace Modules\Shared\Application\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // authorization handled at controller/middleware
    }

    public function rules(): array
    {
        return [
            'identifier' => ['required', 'string', 'max:191'],
            'password'   => ['required', 'string', 'min:6'],
        ];
    }
}
