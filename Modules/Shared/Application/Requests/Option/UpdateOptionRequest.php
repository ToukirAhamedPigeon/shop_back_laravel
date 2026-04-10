<?php

namespace Modules\Shared\Application\Requests\Option;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string'],
            'parentId' => ['nullable', 'string', 'uuid'],
            'hasChild' => ['required', 'string', 'in:true,false'],
            'isActive' => ['nullable', 'string', 'in:true,false'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'isActive' => $this->isActive ?? 'true',
        ]);
    }

    public function getIsActiveBool(): bool
    {
        return filter_var($this->isActive, FILTER_VALIDATE_BOOLEAN);
    }

    public function getHasChildBool(): bool
    {
        return filter_var($this->hasChild, FILTER_VALIDATE_BOOLEAN);
    }

    public function getParentId(): ?string
    {
        return $this->parentId ?? null;
    }
}
