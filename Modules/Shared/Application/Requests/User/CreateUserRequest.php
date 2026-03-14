<?php

namespace Modules\Shared\Application\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;

class CreateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Identity
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
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')
                    ->where(function ($query) {
                        return $query->where('is_deleted', false);
                    })
            ],
            'password' => [
                'required',
                'string',
                'min:6',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).+$/'
            ],
            'confirmedPassword' => ['required', 'same:password'],
            'mobileNo' => [
                'nullable',
                'string',
                Rule::unique('users', 'mobile_no')
                    ->where(function ($query) {
                        return $query->where('is_deleted', false);
                    })
            ],
            'nid' => [
                'nullable',
                'string',
                Rule::unique('users', 'nid')
                    ->where(function ($query) {
                        return $query->where('is_deleted', false);
                    })
            ],

            // Profile - Fix: Accept both ProfileImage and profileImage
            'profileImage' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:5120'],
            'ProfileImage' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:5120'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'address' => ['nullable', 'string', 'max:500'],
            'gender' => ['nullable', 'string', 'in:male,female,other'],
            'dateOfBirth' => ['nullable', 'date', 'before:today'],
            'isActive' => ['nullable', 'string', 'in:true,false'],

            // Roles & Permissions
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
            'mobileNo.unique' => 'Mobile number is already registered.',
            'nid.unique' => 'NID is already registered.',
            'profileImage.max' => 'Profile image must be less than 5MB.',
            'profileImage.image' => 'File must be an image.',
            'profileImage.mimes' => 'Profile image must be a jpeg, png, jpg, gif, or webp file.',
            'ProfileImage.max' => 'Profile image must be less than 5MB.',
            'ProfileImage.image' => 'File must be an image.',
            'ProfileImage.mimes' => 'Profile image must be a jpeg, png, jpg, gif, or webp file.',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Log the incoming data for debugging
        Log::info('CreateUserRequest raw input:', [
            'all' => $this->all(),
            'files' => array_keys($this->allFiles()),
            'has_file' => $this->hasFile('ProfileImage'),
            'method' => $this->method(),
            'content_type' => $this->header('Content-Type')
        ]);

        $data = [];

        // Map incoming fields (which might be capitalized) to expected field names
        $fieldMappings = [
            'Name' => 'name',
            'Username' => 'username',
            'Email' => 'email',
            'Password' => 'password',
            'ConfirmedPassword' => 'confirmedPassword',
            'MobileNo' => 'mobileNo',
            'NID' => 'nid',
            'Bio' => 'bio',
            'Address' => 'address',
            'Gender' => 'gender',
            'DateOfBirth' => 'dateOfBirth',
            'IsActive' => 'isActive',
            'Roles' => 'roles',
            'Permissions' => 'permissions',
        ];

        // Get all input from the request
        $input = $this->all();

        // Map the fields
        foreach ($fieldMappings as $incomingField => $modelField) {
            if (isset($input[$incomingField])) {
                $data[$modelField] = $input[$incomingField];
            }
        }

        // Handle file upload - IMPORTANT FIX
        if ($this->hasFile('ProfileImage')) {
            // The file will be available via $this->file('ProfileImage')
            // We need to make it available as 'profileImage' in the request
            $data['profileImage'] = $this->file('ProfileImage');
        } elseif ($this->hasFile('profileImage')) {
            $data['profileImage'] = $this->file('profileImage');
        }

        // Ensure arrays are properly formatted
        if (isset($data['roles']) && is_string($data['roles'])) {
            $data['roles'] = [$data['roles']];
        }

        if (isset($data['permissions']) && is_string($data['permissions'])) {
            $data['permissions'] = json_decode($data['permissions'], true) ?: [];
        }

        // Set defaults
        if (!isset($data['isActive'])) {
            $data['isActive'] = 'true';
        }

        Log::info('CreateUserRequest mapped data:', [
            'data' => $data,
            'has_profile_image' => isset($data['profileImage'])
        ]);

        $this->merge($data);
    }

    /**
     * Get the validated data with file
     */
    public function validated($key = null, $default = null)
    {
        $validated = parent::validated();

        // Ensure file is in validated data
        if ($this->hasFile('ProfileImage')) {
            $validated['profileImage'] = $this->file('ProfileImage');
        } elseif ($this->hasFile('profileImage')) {
            $validated['profileImage'] = $this->file('profileImage');
        }

        return $key ? ($validated[$key] ?? $default) : $validated;
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->any()) {
                Log::error('CreateUserRequest validation failed', [
                    'errors' => $validator->errors()->toArray(),
                    'input' => $this->all()
                ]);
            }
        });
    }

    public function getIsActiveValue(): ?bool
    {
        return $this->isActive !== null
            ? filter_var($this->isActive, FILTER_VALIDATE_BOOLEAN)
            : true;
    }
}
