<?php

namespace Modules\Shared\Application\Requests\Role;

use Illuminate\Foundation\Http\FormRequest;

class CreateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'names' => ['required', 'string'],
            'guardName' => ['required', 'string'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string'],
            'isActive' => ['nullable', 'string', 'in:true,false'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'guardName' => $this->guardName ?? 'admin',
            'isActive' => $this->isActive ?? 'true',
        ]);
    }

    public function getIsActiveBool(): bool
    {
        return filter_var($this->isActive, FILTER_VALIDATE_BOOLEAN);
    }

    public function getRoleNames(): array
    {
        return array_map('trim', explode('=', $this->names));
    }
}
