<?php

namespace App\Http\Requests\Api\V1\Dictionary;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DictionaryTargetLanguageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'target_language' => [
                'nullable',
                'string',
                'max:16',
                Rule::exists('languages', 'code')->where(fn ($query) => $query->where('is_active', true)),
            ],
        ];
    }

    public function targetLanguage(): ?string
    {
        $value = $this->validated('target_language');

        return is_string($value) && $value !== '' ? $value : null;
    }
}
