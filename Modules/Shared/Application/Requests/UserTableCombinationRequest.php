<?php

namespace Modules\Shared\Application\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserTableCombinationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'userId' => ['required', 'uuid'],
            'tableId' => ['required', 'string'],
            'showColumnCombinations' => ['required', 'array'],
            'showColumnCombinations.*' => ['string'],
        ];
    }
}
