<?php

namespace Modules\Shared\Application\Requests\UserLog;

use Illuminate\Foundation\Http\FormRequest;
use Carbon\Carbon;

class UserLogFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string'],
            'createdAtFrom' => ['nullable', 'date'],
            'createdAtTo' => ['nullable', 'date', 'after_or_equal:createdAtFrom'],
            'collectionName' => ['nullable', 'array'],
            'actionType' => ['nullable', 'array'],
            'createdBy' => ['nullable', 'array'],
            'page' => ['nullable', 'integer', 'min:1'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'sortBy' => ['nullable', 'string', 'in:detail,actionType,modelName,createdAt,createdBy,ipAddress,browser,device,operatingSystem'],
            'sortOrder' => ['nullable', 'string', 'in:asc,desc'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'page' => $this->page ?? 1,
            'limit' => $this->limit ?? 50,
            'sortBy' => $this->sortBy ?? 'createdAt',
            'sortOrder' => $this->sortOrder ?? 'desc',
            'collectionName' => $this->collectionName ?? [],
            'actionType' => $this->actionType ?? [],
            'createdBy' => $this->createdBy ?? [],
        ]);

        // Parse dates if they're provided as strings
        if ($this->createdAtFrom && is_string($this->createdAtFrom)) {
            try {
                $this->merge([
                    'createdAtFrom' => Carbon::parse($this->createdAtFrom)->format('Y-m-d')
                ]);
            } catch (\Exception $e) {
                // If parsing fails, keep as is
            }
        }

        if ($this->createdAtTo && is_string($this->createdAtTo)) {
            try {
                $this->merge([
                    'createdAtTo' => Carbon::parse($this->createdAtTo)->format('Y-m-d')
                ]);
            } catch (\Exception $e) {
                // If parsing fails, keep as is
            }
        }
    }
}
