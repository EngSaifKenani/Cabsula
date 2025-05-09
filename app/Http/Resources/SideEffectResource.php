<?php

namespace App\Http\Resources;

use App\Models\SideEffectCategory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SideEffectResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'category' => new SideEffectCategoryResource($this->whenLoaded('category')),
            'active_ingredients' => ActiveIngredientResource::collection($this->whenLoaded('activeIngredients')),

            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
