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
            'id' => $this->when(array_key_exists('id', $this->getAttributes()), $this->id),
            'name' => $this->when(array_key_exists('name', $this->getAttributes()), $this->name),

            // العلاقة مع التصنيف
            'category' => $this->whenLoaded('category', function() {
                return new SideEffectCategoryResource($this->category);
            }),

            // العلاقة مع المكونات النشطة
            'active_ingredients' => ActiveIngredientResource::collection(
                $this->whenLoaded('activeIngredients')
            ),

            // التواريخ
            'created_at' => $this->when(
                array_key_exists('created_at', $this->getAttributes()),
                optional($this->created_at)->format('Y-m-d H:i:s')
            ),
            'updated_at' => $this->when(
                array_key_exists('updated_at', $this->getAttributes()),
                optional($this->updated_at)->format('Y-m-d H:i:s')
            ),
        ];
    }
}
