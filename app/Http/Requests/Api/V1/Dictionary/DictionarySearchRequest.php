<?php

namespace App\Http\Requests\Api\V1\Dictionary;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DictionarySearchRequest extends FormRequest
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
            'q' => ['required', 'string', 'min:1', 'max:100'],
            'source_language' => [
                'required',
                'string',
                'max:16',
                Rule::exists('languages', 'code')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'target_language' => [
                'nullable',
                'string',
                'max:16',
                Rule::exists('languages', 'code')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'level' => ['nullable', 'string', 'max:8', 'exists:difficulty_levels,code'],
            'part_of_speech' => ['nullable', 'string', 'max:32', 'exists:parts_of_speech,code'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }
}
