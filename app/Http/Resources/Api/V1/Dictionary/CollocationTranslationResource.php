<?php

namespace App\Http\Resources\Api\V1\Dictionary;

use App\Domain\Dictionary\Models\CollocationTranslation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin CollocationTranslation
 */
class CollocationTranslationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'language_id' => $this->language_id,
            'translation' => $this->translation,
            'notes' => $this->notes,
        ];
    }
}
