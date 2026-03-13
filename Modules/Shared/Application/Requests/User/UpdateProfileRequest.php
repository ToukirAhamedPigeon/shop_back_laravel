<?php

namespace Modules\Shared\Application\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('id') ?? $this->user()?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('users', 'email')
                    ->where('is_deleted', false)
                    ->ignore($userId, 'id')
            ],
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
            'address' => ['nullable', 'string', 'max:500'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'gender' => ['nullable', 'string', 'in:male,female,other'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'profile_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
            'remove_profile_image' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => $this->name ?? '',
            'email' => $this->email ?? null,
            'mobile_no' => $this->mobile_no ?? null,
            'nid' => $this->nid ?? null,
            'address' => $this->address ?? null,
            'bio' => $this->bio ?? null,
            'gender' => $this->gender ?? null,
            'date_of_birth' => $this->date_of_birth ?? null,
            'remove_profile_image' => filter_var($this->remove_profile_image ?? false, FILTER_VALIDATE_BOOLEAN),
        ]);
    }
}
