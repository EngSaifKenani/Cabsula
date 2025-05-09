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
            'id' => $this->id, // عادةً يتم جلبه دائمًا
            'scientific_name' => $this->when(array_key_exists('scientific_name', $this->getAttributes()), $this->scientific_name),
            'description' => $this->when(array_key_exists('description', $this->getAttributes()), $this->description),
            'cas_number' => $this->when(array_key_exists('cas_number', $this->getAttributes()), $this->cas_number),
            'unii_code' => $this->when(array_key_exists('unii_code', $this->getAttributes()), $this->unii_code),
            'is_active' => $this->when(array_key_exists('is_active', $this->getAttributes()), $this->is_active),
            'status' => $this->when(array_key_exists('is_active', $this->getAttributes()), $this->is_active), // يعتمد على is_active

            // علاقات
            'drugs' => DrugResource::collection($this->whenLoaded('drugs')),
            'side_effects' => SideEffectResource::collection($this->whenLoaded('sideEffects')),
            'therapeutic_uses' => TherapeuticUseResource::collection($this->whenLoaded('therapeuticUses')),

            // عدد العلاقات
            'therapeutic_uses_count' => $this->whenCounted('therapeuticUses'),
            'drugs_count' => $this->whenCounted('drugs'),
            'side_effects_count' => $this->whenCounted('sideEffects'),

            // التواريخ
            'created_at' => $this->when(array_key_exists('created_at', $this->getAttributes()), optional($this->created_at)->format('Y-m-d H:i:s')),
            'updated_at' => $this->when(array_key_exists('updated_at', $this->getAttributes()), optional($this->updated_at)->format('Y-m-d H:i:s')),
            'deleted_at' => $this->when(array_key_exists('deleted_at', $this->getAttributes()) && $this->deleted_at !== null, optional($this->deleted_at)->format('Y-m-d H:i:s')),
        ];
    }
}
