<?php

namespace Modules\Shared\Application\Requests\Users;

use Illuminate\Foundation\Http\FormRequest;

class UserFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Search & pagination
            'q'         => ['nullable', 'string'],
            'page'      => ['nullable', 'integer', 'min:1'],
            'limit'     => ['nullable', 'integer', 'min:1', 'max:1000'],
            'sortBy'    => ['nullable', 'string'],
            'sortOrder' => ['nullable', 'in:asc,desc'],

            // String-based booleans from frontend
            'isActiveStr'  => ['nullable', 'string', 'in:true,false'],
            'isDeletedStr' => ['nullable', 'string', 'in:true,false'],

            // Multi-select filters
            'roles'       => ['nullable', 'array'],
            'roles.*'     => ['string'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string'],
            'gender'      => ['nullable', 'array'],
            'gender.*'    => ['string'],
            'createdBy'   => ['nullable', 'array'],
            'createdBy.*' => ['string', 'uuid'],
            'updatedBy'   => ['nullable', 'array'],
            'updatedBy.*' => ['string', 'uuid'],

            // Date filtering
            'dateType'    => ['nullable', 'array'],
            'dateType.*'  => ['string'],
            'from'        => ['nullable', 'date'],
            'to'          => ['nullable', 'date'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'page'      => $this->page ?? 1,
            'limit'     => $this->limit ?? 10,
            'sortBy'    => $this->sortBy ?? 'createdAt',
            'sortOrder' => $this->sortOrder ?? 'desc',
        ]);
    }

    // Parsed boolean values
    public function getIsActive(): ?bool
    {
        return $this->isActiveStr !== null
            ? filter_var($this->isActiveStr, FILTER_VALIDATE_BOOLEAN)
            : null;
    }

    public function getIsDeleted(): ?bool
    {
        return $this->isDeletedStr !== null
            ? filter_var($this->isDeletedStr, FILTER_VALIDATE_BOOLEAN)
            : null;
    }
}
