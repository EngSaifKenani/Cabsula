<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TherapeuticUseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->when(array_key_exists('id', $this->getAttributes()), $this->id),

            'name' => $this->when(array_key_exists('name', $this->getAttributes()), $this->name),
            'description' => $this->when(array_key_exists('description', $this->getAttributes()), $this->description),
            'image_url' => $this->when(array_key_exists('image', $this->getAttributes()), $this->image ? asset('storage/' . $this->image) : null),

            'created_at' => $this->when(
                !is_null($this->created_at),
                fn () => $this->created_at instanceof \Carbon\Carbon
                    ? $this->created_at->format('Y-m-d H:i:s')
                    : (string) $this->created_at
            ),

            'updated_at' => $this->when(
                !is_null($this->updated_at),
                fn () => $this->updated_at instanceof \Carbon\Carbon
                    ? $this->updated_at->format('Y-m-d H:i:s')
                    : (string) $this->updated_at
            ),
            'active_ingredients' => ActiveIngredientResource::collection(
                $this->whenLoaded('activeIngredients')
            ),
            'active_ingredients_count' => $this->whenCounted('activeIngredients'),
        ];
    }
}
