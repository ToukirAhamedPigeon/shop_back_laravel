<?php

namespace Modules\Shared\Application\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('id') ?? $this->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => [
                'required',
                'string',
                'min:4',
                'max:255',
                Rule::unique('users', 'username')
                    ->where('is_deleted', false)
                    ->ignore($userId, 'id')
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')
                    ->where('is_deleted', false)
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
                    ->where('is_deleted', false)
                    ->ignore($userId, 'id')
            ],
            'nid' => [
                'nullable',
                'string',
                Rule::unique('users', 'nid')
                    ->where('is_deleted', false)
                    ->ignore($userId, 'id')
            ],
            'profile_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
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
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => $this->name ?? '',
            'username' => $this->username ?? '',
            'email' => $this->email ?? '',
            'password' => $this->password ?? null,
            'confirmedPassword' => $this->confirmedPassword ?? null,
            'mobile_no' => $this->mobile_no ?? null,
            'nid' => $this->nid ?? null,
            'address' => $this->address ?? null,
            'is_active' => $this->is_active ?? null,
            'remove_profile_image' => filter_var($this->remove_profile_image ?? false, FILTER_VALIDATE_BOOLEAN),
            'roles' => $this->roles ?? [],
            'permissions' => $this->permissions ?? [],
        ]);
    }

    public function getIsActiveValue(): ?bool
    {
        return $this->is_active !== null
            ? filter_var($this->is_active, FILTER_VALIDATE_BOOLEAN)
            : null;
    }
}
