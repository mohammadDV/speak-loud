<?php

namespace App\Http\Resources\Api\V1\Dictionary;

use App\Domain\Dictionary\Models\Example;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Example
 */
class ExampleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sentence' => $this->sentence,
            'usage_note' => $this->usage_note,
            'language_id' => $this->language_id,
            'translations' => ExampleTranslationResource::collection($this->whenLoaded('translations')),
        ];
    }
}
