<?php

namespace App\Http\Resources\Api\V1\Dictionary;

use App\Models\Language;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Language
 */
class LanguageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name_en,
            'native_name' => $this->name_native,
            'script' => $this->script,
            'direction' => $this->direction,
        ];
    }
}
