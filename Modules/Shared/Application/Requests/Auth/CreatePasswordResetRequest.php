<?php

namespace Modules\Shared\Application\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class CreatePasswordResetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // controller will handle auth if needed
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:191'],
        ];
    }
}
