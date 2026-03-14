<?php

namespace Modules\Shared\Application\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('id');

        Log::info('UpdateUserRequest rules - userId:', ['id' => $userId]);

        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => [
                'required',
                'string',
                'min:4',
                'max:255',
                Rule::unique('users', 'username')
                    ->where(function ($query) {
                        return $query->where('is_deleted', false);
                    })
                    ->ignore($userId, 'id')
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')
                    ->where(function ($query) {
                        return $query->where('is_deleted', false);
                    })
                    ->ignore($userId, 'id')
            ],
            'password' => [
                'nullable',
                'string',
                'min:6',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).+$/'
            ],
            'confirmedPassword' => ['nullable', 'same:password'],
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
            'profile_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:5120'],
            'remove_profile_image' => ['nullable', 'boolean'],
            'address' => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable', 'string', 'in:true,false'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string', 'exists:roles,name'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ];
    }

    public function messages(): array
    {
        return [
            'password.regex' => 'Password must contain uppercase, lowercase, number and special character.',
            'confirmedPassword.same' => 'Password confirmation does not match.',
            'username.unique' => 'Username is already taken.',
            'email.unique' => 'Email is already registered.',
            'mobile_no.unique' => 'Mobile number is already registered.',
            'nid.unique' => 'NID is already registered.',
            'profile_image.max' => 'Profile image must be less than 5MB.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $data = [];

        // Get all input data (now properly populated by middleware)
        $input = $this->all();

        Log::info('UpdateUserRequest prepareForValidation - raw input:', $input);

        // Handle regular fields
        $fields = ['name', 'username', 'email', 'password', 'confirmedPassword',
                   'mobile_no', 'nid', 'address', 'is_active'];

        foreach ($fields as $field) {
            if (isset($input[$field])) {
                $data[$field] = $input[$field];
            }
        }

        // Handle boolean fields
        $data['remove_profile_image'] = filter_var(
            $input['remove_profile_image'] ?? false,
            FILTER_VALIDATE_BOOLEAN
        );

        // Handle roles - might be a single value or array
        if (isset($input['roles'])) {
            if (is_array($input['roles'])) {
                $data['roles'] = $input['roles'];
            } else {
                // If it's a single role string, convert to array
                $data['roles'] = [$input['roles']];
            }
        } else {
            $data['roles'] = [];
        }

        // Handle permissions
        if (isset($input['permissions'])) {
            if (is_array($input['permissions'])) {
                $data['permissions'] = $input['permissions'];
            } else {
                $data['permissions'] = json_decode($input['permissions'], true) ?? [];
            }
        } else {
            $data['permissions'] = [];
        }

        Log::info('UpdateUserRequest prepareForValidation - merged data:', $data);

        $this->merge($data);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->any()) {
                Log::error('UpdateUserRequest validation failed', [
                    'errors' => $validator->errors()->toArray(),
                    'input' => $this->all()
                ]);
            }
        });
    }
}
