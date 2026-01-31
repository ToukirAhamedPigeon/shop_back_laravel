<?php

namespace Modules\Shared\Application\Requests\UserLog;

use Illuminate\Foundation\Http\FormRequest;

class UserLogFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'limit'      => ['sometimes', 'integer', 'min:1', 'max:1000'],
            'skip'       => ['sometimes', 'integer', 'min:0'],
            'sortBy'     => ['sometimes', 'string'],
            'sortOrder'  => ['sometimes', 'in:asc,desc'],
            'search'     => ['sometimes', 'string'],
            'where'      => ['sometimes', 'array'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'limit'     => $this->limit ?? 250,
            'skip'      => $this->skip ?? 0,
            'sortOrder' => $this->sortOrder ?? 'asc',
        ]);
    }
}
