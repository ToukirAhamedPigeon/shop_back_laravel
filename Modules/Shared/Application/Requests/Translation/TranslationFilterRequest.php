<?php

namespace Modules\Shared\Application\Requests\Translation;

use Illuminate\Foundation\Http\FormRequest;

class TranslationFilterRequest extends FormRequest
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
            'sortBy' => ['nullable', 'string', 'in:key,module,createdAt,updatedAt'],
            'sortOrder' => ['nullable', 'string', 'in:asc,desc'],
            'startDate' => ['nullable', 'date'],
            'endDate' => ['nullable', 'date'],
            'modules' => ['nullable', 'array'],
            'modules.*' => ['string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'page' => $this->page ?? 1,
            'limit' => $this->limit ?? 10,
            'sortBy' => $this->sortBy ?? 'createdAt',
            'sortOrder' => $this->sortOrder ?? 'desc',
        ]);
    }

    public function getSortColumn(): string
    {
        $sortMap = [
            'createdAt' => 'created_at',
            'updatedAt' => 'updated_at',
        ];
        return $sortMap[$this->sortBy] ?? $this->sortBy;
    }
}
