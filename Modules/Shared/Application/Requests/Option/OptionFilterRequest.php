<?php

namespace Modules\Shared\Application\Requests\Option;

use Illuminate\Foundation\Http\FormRequest;

class OptionFilterRequest extends FormRequest
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
            'parentId' => ['nullable', 'string'],
            'createdFrom' => ['nullable', 'date'],
            'createdTo' => ['nullable', 'date'],
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
        if ($this->isActiveStr === 'all' || empty($this->isActiveStr)) {
            return null;
        }
        return filter_var($this->isActiveStr, FILTER_VALIDATE_BOOLEAN);
    }

    public function getIsDeleted(): ?bool
    {
        if (empty($this->isDeletedStr)) {
            return false;
        }
        return filter_var($this->isDeletedStr, FILTER_VALIDATE_BOOLEAN);
    }

    public function getParentIdFilter(): ?string
    {
        if (empty($this->parentId) || $this->parentId === 'all') {
            return null;
        }
        if ($this->parentId === 'null') {
            return 'null';
        }
        return $this->parentId;
    }

    public function filterByNullParent(): bool
    {
        return $this->parentId === 'null';
    }
}
