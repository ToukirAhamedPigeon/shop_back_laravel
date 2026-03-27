<?php

namespace Modules\Shared\Application\Requests\Permission;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Shared\Infrastructure\Helpers\NameExpander;

class CreatePermissionRequest extends FormRequest
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
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string'],
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

    public function getPermissionNames(): array
    {
        return NameExpander::expandNames($this->names);
    }

    public function getExpandedPermissions(): array
    {
        if (empty($this->permissions)) {
            return [];
        }
        return NameExpander::expandPermissionNames($this->permissions);
    }
}
