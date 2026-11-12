<?php

namespace App\Http\Resources\Api\V1\Dictionary;

use App\Domain\Dictionary\Models\Sense;
use App\Domain\Dictionary\Support\EnumValue;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Sense
 */
class SenseResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'definition' => $this->definition,
            'usage_label' => $this->usage_label,
            'domain' => $this->domain,
            'sort_order' => $this->sort_order,
            'status' => EnumValue::string($this->status),
            'part_of_speech' => $this->whenLoaded('partOfSpeech', fn () => [
                'code' => $this->partOfSpeech?->code,
                'display_name' => $this->partOfSpeech?->display_name,
            ]),
            'level' => $this->whenLoaded('primaryLevel', fn () => $this->primaryLevel?->difficultyLevel?->code),
            'grammar' => $this->whenLoaded('germanNounDetail', function () {
                if ($this->germanNounDetail === null) {
                    return null;
                }

                return [
                    'article' => EnumValue::string($this->germanNounDetail->article),
                    'gender' => EnumValue::string($this->germanNounDetail->grammatical_gender),
                    'plural_form' => $this->germanNounDetail->plural_form,
                    'notes' => $this->germanNounDetail->grammar_notes,
                ];
            }),
            'translations' => SenseTranslationResource::collection($this->whenLoaded('translations')),
            'examples' => ExampleResource::collection($this->whenLoaded('examples')),
            'collocations' => CollocationResource::collection($this->whenLoaded('collocations')),
        ];
    }
}
