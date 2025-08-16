<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DrugResource extends JsonResource
{
    /**
     * Transform the resource into an array.
/    *
     * @return array<string, mixed>
/    */
 public function toArray(Request $request): array  {
       return [
           'id' => $this->id,
           'name' => $this->name,
           'description' => $this->description,
           'admin_notes' => $this->when(auth()->user()->isPharmacist()||auth()->user()->isAdmin(), $this->admin_notes),
           'image_url' => $this->image ?  : null,
           'total_sold' => $this->when(auth()->user()->isAdmin(), $this->total_sold),

           'is_requires_prescription' => $this->is_requires_prescription,
           'created_at' => $this->when($this->hasAttribute('created_at'), function() {
               return $this->created_at?->format('Y-m-d H:i:s');
           }),
           'updated_at' => $this->when($this->hasAttribute('updated_at'), function() {
               return $this->updated_at?->format('Y-m-d H:i:s');
           }),
            'deleted_at' => $this->whenNotNull($this->deleted_at?->format('Y-m-d H:i:s')),

           'form' => FormResource::make($this->whenLoaded('form')),
           'manufacturer' => ManufacturerResource::make($this->whenLoaded('manufacturer')),
           'recommended_dosage' => RecommendedDosageResource::make($this->whenLoaded('recommendedDosage')),
           'active_ingredients' => ActiveIngredientResource::collection($this->whenLoaded('activeIngredients')),
            'alternative_drugs' => DrugResource::collection(
                $this->whenLoaded('alternativeDrugs'),
            ),

           'valid_batches' => auth()->user() && auth()->user()->role === 'customer'
               ? BatchResource::collection($this->validBatches->take(1))
               : BatchResource::collection($this->validBatches),

           'valid_batches_count' => $this->validBatches->count(),

          /*  'lastValid_batch' => new BatchResource(
                $this->whenLoaded('lastValidBatch')
            ),*/
           'active_ingredients_count' => $this->whenCounted('activeIngredients'),
           'alternative_drugs_count' => $this->whenCounted('alternativeDrugs'),



        ];}
    protected function hasAttribute($attribute)
    {
        return isset($this->resource) &&
            (in_array($attribute, $this->resource->getFillable()) ||
                array_key_exists($attribute, $this->resource->getAttributes()));
    }
}
