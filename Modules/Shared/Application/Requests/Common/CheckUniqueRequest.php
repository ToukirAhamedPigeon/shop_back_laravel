<?php

namespace Modules\Shared\Application\Requests\Common;

use Illuminate\Foundation\Http\FormRequest;

class CheckUniqueRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'model' => ['required', 'string'],
            'fieldName' => ['required', 'string'],
            'fieldValue' => ['required', 'string'],

            'exceptFieldName' => ['nullable', 'string'],
            'exceptFieldValue' => ['nullable', 'string'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'model.required' => 'The model name is required.',
            'fieldName.required' => 'The field name is required.',
            'fieldValue.required' => 'The field value is required.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'model' => $this->model ?? '',
            'fieldName' => $this->fieldName ?? '',
            'fieldValue' => $this->fieldValue ?? '',
            'exceptFieldName' => $this->exceptFieldName ?? null,
            'exceptFieldValue' => $this->exceptFieldValue ?? null,
        ]);
    }

    /**
     * Get the model name (snake_case to PascalCase conversion if needed)
     */
    public function getModelClass(): string
    {
        $model = $this->model;

        // Convert snake_case to PascalCase (e.g., "user" -> "User", "user_profile" -> "UserProfile")
        $model = str_replace('_', '', ucwords($model, '_'));

        // You might want to map to full namespace based on your structure
        // For example, if model is "user", return "Modules\Shared\Infrastructure\Models\EloquentUser"
        // Adjust this based on your actual model namespace convention
        return $model;
    }

    /**
     * Get the except ID value for ignore check
     */
    public function getExceptId(): ?string
    {
        if ($this->exceptFieldName === 'id' && !empty($this->exceptFieldValue)) {
            return $this->exceptFieldValue;
        }

        return null;
    }
}
