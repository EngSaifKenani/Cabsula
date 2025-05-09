<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecommendedDosageResource extends JsonResource
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

            'dosage' => $this->when(array_key_exists('dosage', $this->getAttributes()), $this->dosage),
            'notes' => $this->when(array_key_exists('notes', $this->getAttributes()), $this->notes),

            'drugs' => DrugResource::collection($this->whenLoaded('drugs')),
            'drugs_count' => $this->whenCounted('drugs'),

            'created_at' => $this->when(array_key_exists('created_at', $this->getAttributes()), optional($this->created_at)->format('Y-m-d H:i:s')),
            'updated_at' => $this->when(array_key_exists('updated_at', $this->getAttributes()), optional($this->updated_at)->format('Y-m-d H:i:s')),
        ];
    }
}
