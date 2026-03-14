<?php

namespace Modules\Shared\Application\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('id') ?? $this->user()?->id;

        Log::info('UpdateProfileRequest rules - userId:', ['id' => $userId]);

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('users', 'email')
                    ->where(function ($query) {
                        return $query->where('is_deleted', false);
                    })
                    ->ignore($userId, 'id')
            ],
            'mobile_no' => [
                'nullable',
                'string',
                Rule::unique('users', 'mobile_no')
                    ->where(function ($query) {
                        return $query->where('is_deleted', false);
                    })
                    ->ignore($userId, 'id')
            ],
            'nid' => [
                'nullable',
                'string',
                Rule::unique('users', 'nid')
                    ->where(function ($query) {
                        return $query->where('is_deleted', false);
                    })
                    ->ignore($userId, 'id')
            ],
            'address' => ['nullable', 'string', 'max:500'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'gender' => ['nullable', 'string', 'in:male,female,other'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'profile_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:5120'],
            'remove_profile_image' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'profile_image.max' => 'Profile image must be less than 5MB.',
            'profile_image.image' => 'File must be an image.',
            'profile_image.mimes' => 'Profile image must be a jpeg, png, jpg, gif, or webp file.',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Get all input data (now properly populated by middleware)
        $input = $this->all();

        Log::info('UpdateProfileRequest prepareForValidation - raw input:', $input);

        $data = [];

        // Handle regular fields
        $fields = ['name', 'email', 'mobile_no', 'nid', 'address', 'bio', 'gender', 'date_of_birth'];

        foreach ($fields as $field) {
            if (isset($input[$field]) && $input[$field] !== '') {
                $data[$field] = $input[$field];
            }
        }

        // Handle boolean fields
        $data['remove_profile_image'] = filter_var(
            $input['remove_profile_image'] ?? false,
            FILTER_VALIDATE_BOOLEAN
        );

        Log::info('UpdateProfileRequest prepareForValidation - merged data:', $data);

        $this->merge($data);
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->any()) {
                Log::error('UpdateProfileRequest validation failed', [
                    'errors' => $validator->errors()->toArray(),
                    'input' => $this->all()
                ]);
            }
        });
    }
}
