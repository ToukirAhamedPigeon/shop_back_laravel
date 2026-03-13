<?php

namespace Modules\Shared\Application\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'username' => ['required', 'string', 'min:4', 'max:255', Rule::unique('users', 'username')->where('is_deleted', false)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->where('is_deleted', false)],
            'password' => [
                'required',
                'string',
                'min:6',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).+$/'
            ],
            'confirmedPassword' => ['required', 'same:password'],
            'mobileNo' => ['nullable', 'string', Rule::unique('users', 'mobile_no')->where('is_deleted', false)],
            'nid' => ['nullable', 'string', Rule::unique('users', 'nid')->where('is_deleted', false)],

            // Profile
            'profileImage' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
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
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => $this->name ?? '',
            'username' => $this->username ?? '',
            'email' => $this->email ?? '',
            'password' => $this->password ?? '',
            'confirmedPassword' => $this->confirmedPassword ?? '',
            'mobileNo' => $this->mobileNo ?? null,
            'nid' => $this->nid ?? null,
            'bio' => $this->bio ?? null,
            'address' => $this->address ?? null,
            'gender' => $this->gender ?? null,
            'dateOfBirth' => $this->dateOfBirth ?? null,
            'isActive' => $this->isActive ?? 'true',
            'roles' => $this->roles ?? [],
            'permissions' => $this->permissions ?? [],
        ]);
    }

    public function getIsActiveValue(): ?bool
    {
        return $this->isActive !== null
            ? filter_var($this->isActive, FILTER_VALIDATE_BOOLEAN)
            : true;
    }
}
