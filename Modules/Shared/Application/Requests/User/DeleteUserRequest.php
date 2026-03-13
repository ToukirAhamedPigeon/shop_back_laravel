<?php

namespace Modules\Shared\Application\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class DeleteUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => ['required', 'string', 'uuid', 'exists:users,id'],
            'permanent' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'id' => $this->id ?? $this->route('id') ?? '',
            'permanent' => $this->permanent ?? false,
        ]);
    }
}
