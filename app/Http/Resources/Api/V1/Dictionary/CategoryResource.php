<?php

namespace App\Http\Resources\Api\V1\Dictionary;

use App\Domain\Dictionary\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Category
 */
class CategoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'display_name' => $this->display_name,
            'description' => $this->description,
            'sort_order' => $this->sort_order,
        ];
    }
}
