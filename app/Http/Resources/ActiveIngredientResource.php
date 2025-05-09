<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActiveIngredientResource extends JsonResource
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
            'scientific_name' => $this->scientific_name ?? $this->scientific_name,
            'description' => $this->translated->description ?? $this->description,
            'cas_number' => $this->cas_number,
            'unii_code' => $this->unii_code,
            'is_active' => $this->is_active,
            'status' => $this->is_active ? 'Active' : 'Inactive',

            'drugs' => DrugResource::collection($this->whenLoaded('drugs')),
            'side_effects' => SideEffectResource::collection(
                $this->whenLoaded('sideEffects')
            ),

            'therapeutic_uses' => TherapeuticUseResource::collection(
                $this->whenLoaded('therapeuticUses')
            ),
            'therapeutic_uses_count' => $this->whenCounted('therapeuticUses'),


            'drugs_count' => $this->whenCounted('drugs'),
            'side_effects_count' => $this->whenCounted('sideEffects'),

      /*      'translations' => $this->when(
                $request->user()?->can('view-translations'),
                $this->translations
            ),*/

            // التواريخ
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            'deleted_at' => $this->whenNotNull($this->deleted_at?->format('Y-m-d H:i:s')),
        ];
    }
}
