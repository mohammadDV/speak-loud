<?php

namespace App\Http\Resources\Api\V1\Dictionary;

use App\Domain\Dictionary\Models\SenseTranslation;
use App\Domain\Dictionary\Support\EnumValue;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin SenseTranslation
 */
class SenseTranslationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'target_language_id' => $this->target_language_id,
            'translation_text' => $this->translation_text,
            'translation_type' => EnumValue::string($this->translation_type),
            'notes' => $this->notes,
            'register' => $this->register,
            'sort_order' => $this->sort_order,
        ];
    }
}
