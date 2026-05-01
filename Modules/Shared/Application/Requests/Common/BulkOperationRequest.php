<?php

namespace Modules\Shared\Application\Requests\Common;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class BulkOperationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ids' => ['required', 'array'],
            'ids.*' => ['string'],
            'permanent' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'ids' => $this->ids ?? [],
            'permanent' => $this->permanent ?? false,
        ]);
    }

    /**
     * Validate IDs and return invalid ones
     *
     * @return array{isValid: bool, invalidIds: array}
     */
    public function validateIds(): array
    {
        $invalidIds = [];
        foreach ($this->ids as $id) {
            if (!Str::isUuid($id)) {
                $invalidIds[] = $id;
            }
        }
        return [
            'isValid' => empty($invalidIds),
            'invalidIds' => $invalidIds
        ];
    }

    /**
     * Get validated GUIDs
     *
     * @return array<string>
     */
    public function getGuids(): array
    {
        $guids = [];
        foreach ($this->ids as $id) {
            if (Str::isUuid($id)) {
                $guids[] = $id;
            }
        }
        return $guids;
    }
}
