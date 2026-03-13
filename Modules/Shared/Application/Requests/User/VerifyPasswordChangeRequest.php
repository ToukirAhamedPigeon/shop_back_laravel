<?php

namespace Modules\Shared\Application\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class VerifyPasswordChangeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token' => ['required', 'string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'token' => $this->token ?? '',
        ]);
    }
}
