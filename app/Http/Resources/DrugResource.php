<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

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
            'barcode' => $this->barcode,
            'description' => $this->description,
            'status' => $this->status,
            'unit_price' => $this->whenLoaded('batches', function () {
                $firstAvailableBatch = $this->batches
                    ->where('status', 'available')
                    ->sortByDesc('created_at')
                    ->first();
                return $firstAvailableBatch?->unit_price;
            }),
            'admin_notes' => $this->when(auth()->user()?->isPharmacist() || auth()->user()?->isAdmin(), $this->admin_notes),
            'image' => $this->image ? asset('storage/' . $this->image) : null,
            'is_requires_prescription' => $this->is_requires_prescription,

            'total_quantity_in_stock' => $this->whenLoaded('batches', function () {
                return $this->batches->where('status', 'available')->sum('stock');
            }),

            'created_at' => $this->when($this->created_at, function () {
                return $this->created_at->format('Y-m-d H:i:s');
            }),
            'updated_at' => $this->when($this->updated_at, function () {
                return $this->updated_at->format('Y-m-d H:i:s');
            }),
            'deleted_at' => $this->whenNotNull($this->deleted_at?->format('Y-m-d H:i:s')),

            'form' => FormResource::make($this->whenLoaded('form')),
            'manufacturer' => ManufacturerResource::make($this->whenLoaded('manufacturer')),
            'recommended_dosage' => RecommendedDosageResource::make($this->whenLoaded('recommendedDosage')),
            'active_ingredients' => ActiveIngredientResource::collection($this->whenLoaded('activeIngredients')),

            // ** هنا التعديل **
            // إرسال جميع الدفعات في قائمة واحدة
            'all_batches' => BatchResource::collection($this->whenLoaded('batches')),

            'all_batches_count' => $this->whenLoaded('batches', function () {
                return $this->batches->count();
            }),
        ];
    }
}
