<?php

namespace Modules\Shared\Application\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class ChangePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'currentPassword' => ['required', 'string'],
            'newPassword' => [
                'required',
                'string',
                'min:6',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).+$/'
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'newPassword.regex' => 'Password must contain uppercase, lowercase, number and special character.',
            'newPassword.min' => 'Password must be at least 6 characters.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'currentPassword' => $this->currentPassword ?? '',
            'newPassword' => $this->newPassword ?? '',
        ]);
    }
}
