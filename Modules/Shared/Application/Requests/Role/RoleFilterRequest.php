<?php

namespace Modules\Shared\Application\Requests\Role;

use Illuminate\Foundation\Http\FormRequest;

class RoleFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string'],
            'page' => ['nullable', 'integer', 'min:1'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'sortBy' => ['nullable', 'string'],
            'sortOrder' => ['nullable', 'in:asc,desc'],
            'isActiveStr' => ['nullable', 'string', 'in:all,true,false'],
            'isDeletedStr' => ['nullable', 'string', 'in:true,false'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'page' => $this->page ?? 1,
            'limit' => $this->limit ?? 10,
            'sortBy' => $this->sortBy ?? 'createdAt',
            'sortOrder' => $this->sortOrder ?? 'desc',
            'isActiveStr' => $this->isActiveStr ?? 'all',
            'isDeletedStr' => $this->isDeletedStr ?? 'false',
        ]);
    }

    public function getIsActive(): ?bool
    {
        // Handle "all" or empty string
        if ($this->isActiveStr === 'all' || empty($this->isActiveStr)) {
            return null;
        }

        // Parse boolean from string "true" or "false"
        return filter_var($this->isActiveStr, FILTER_VALIDATE_BOOLEAN);
    }

    public function getIsDeleted(): ?bool
    {
        // Handle empty or null
        if (empty($this->isDeletedStr)) {
            return false; // Default to false (show non-deleted)
        }

        // Parse boolean from string "true" or "false"
        return filter_var($this->isDeletedStr, FILTER_VALIDATE_BOOLEAN);
    }
}
