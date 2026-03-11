<?php

namespace Modules\Shared\Application\Requests\Common;

use Illuminate\Foundation\Http\FormRequest;

class SelectOptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'limit'     => ['sometimes', 'integer', 'min:1', 'max:1000'],
            'skip'      => ['sometimes', 'integer', 'min:0'],
            'search'    => ['sometimes', 'nullable', 'string'],
            'where'     => ['sometimes', 'array'],
            'sortOrder' => ['sometimes', 'in:asc,desc'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'limit'     => $this->limit ?? 100,
            'skip'      => $this->skip ?? 0,
            'sortOrder' => $this->sortOrder ?? 'asc',
            'where'     => $this->where ?? [],
            'search'    => $this->search ?? null,
        ]);
    }
}
