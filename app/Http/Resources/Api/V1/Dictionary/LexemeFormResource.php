<?php

namespace App\Http\Resources\Api\V1\Dictionary;

use App\Domain\Dictionary\Models\LexemeForm;
use App\Domain\Dictionary\Support\EnumValue;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin LexemeForm
 */
class LexemeFormResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'form' => $this->form,
            'form_type' => EnumValue::string($this->form_type),
            'is_primary' => $this->is_primary,
            'grammatical_features' => $this->grammatical_features,
        ];
    }
}
