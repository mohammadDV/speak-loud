<?php

namespace App\Http\Resources\Api\V1\Dictionary;

use App\Domain\Dictionary\Models\Collocation;
use App\Domain\Dictionary\Support\EnumValue;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Collocation
 */
class CollocationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'phrase' => $this->phrase,
            'pattern_type' => $this->pattern_type,
            'phrase_type' => EnumValue::string($this->phrase_type),
            'explanation' => $this->explanation,
            'frequency_label' => $this->frequency_label,
            'translations' => CollocationTranslationResource::collection($this->whenLoaded('translations')),
        ];
    }
}
