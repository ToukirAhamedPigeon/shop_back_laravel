<?php

namespace Modules\Shared\Application\Requests\Translation;

use Illuminate\Foundation\Http\FormRequest;

class CreateTranslationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'key' => ['required', 'string', 'min:2', 'max:255'],
            'module' => ['required', 'string', 'min:2', 'max:100'],
            'englishValue' => ['required', 'string'],
            'banglaValue' => ['required', 'string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'module' => $this->module ?? 'common',
        ]);
    }
}
