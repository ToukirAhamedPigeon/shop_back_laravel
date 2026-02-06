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
            'q'         => ['nullable', 'string'],
            'page'      => ['nullable', 'integer', 'min:1'],
            'limit'     => ['nullable', 'integer', 'min:1', 'max:1000'],
            'sortBy'    => ['nullable', 'string'],
            'sortOrder' => ['nullable', 'in:asc,desc'],
            'isActive'  => ['nullable', 'boolean'],
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
}
