<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DrugResource extends JsonResource
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
            'description' => $this->description,
            'admin_notes' =>  $this->admin_notes,
            'status' =>  $this->status,
            'price' => $this->price,
            'image_url' => $this->image ? asset('storage/' . $this->image) : null, // افترض أن الصورة مُخزنة في storage
            'profit_amount' => $this->profit_amount,
            'stock' => $this->stock,
            'is_active' => $this->is_active,
            'is_requires_prescription' => $this->is_requires_prescription,

            // التواريخ
            'production_date' => $this->formatDate($this->production_date),
            'expiry_date' => $this->formatDate($this->expiry_date),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            'deleted_at' => $this->whenNotNull($this->deleted_at?->format('Y-m-d H:i:s')),

            // العلاقات
            'form' => FormResource::make($this->whenLoaded('form')),
            'manufacturer' => ManufacturerResource::make($this->whenLoaded('manufacturer')),
            'recommended_dosage' => RecommendedDosageResource::make($this->whenLoaded('recommendedDosage')),
            'active_ingredients' => ActiveIngredientResource::collection(
                $this->whenLoaded('activeIngredients')
            ),
            'alternative_drugs' => DrugResource::collection(
                $this->whenLoaded('alternativeDrugs')
            ),

            // عدد العلاقات
            'active_ingredients_count' => $this->whenCounted('activeIngredients'),
            'alternative_drugs_count' => $this->whenCounted('alternativeDrugs'),


        ];
    }

    private function formatDate($date)
    {
        if (empty($date)) return null;
        return is_string($date) ? $date : $date->format('Y-m-d');
    }
}
