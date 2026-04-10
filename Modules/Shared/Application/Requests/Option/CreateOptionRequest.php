<?php

namespace Modules\Shared\Application\Requests\Option;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Shared\Infrastructure\Helpers\NameExpander;

class CreateOptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'names' => ['required', 'string'],
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

    public function getOptionNames(): array
    {
        // Get names from the request - preserve case
        $namesString = $this->names;

        // Split by "=" and trim each name, but DO NOT convert to lowercase
        $names = array_map('trim', explode('=', $namesString));

        // Filter out empty names
        $names = array_filter($names, function($name) {
            return !empty($name);
        });

        // Return names with original case preserved
        return array_values($names);
    }

    public function getParentId(): ?string
    {
        return $this->parentId ?? null;
    }
}
