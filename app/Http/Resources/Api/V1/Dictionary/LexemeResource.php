<?php

namespace App\Http\Resources\Api\V1\Dictionary;

use App\Domain\Dictionary\Models\Lexeme;
use App\Domain\Dictionary\Support\EnumValue;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Lexeme
 */
class LexemeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'lemma' => $this->lemma,
            'slug' => $this->slug,
            'pronunciation' => $this->pronunciation,
            'disambiguation' => $this->disambiguation,
            'status' => EnumValue::string($this->status),
            'language' => new LanguageResource($this->whenLoaded('language')),
            'forms' => LexemeFormResource::collection($this->whenLoaded('forms')),
            'senses' => SenseResource::collection($this->whenLoaded('senses')),
        ];
    }
}
