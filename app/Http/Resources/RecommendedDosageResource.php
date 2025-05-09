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

            'dosage' =>$this->dosage,
            'notes' => $this->notes,

            'drugs' => DrugResource::collection($this->whenLoaded('drugs')),
            'drugs_count' => $this->whenCounted('drugs'),
         /*   'translations' => $this->when(
                $request->user()?->can('view-translations'),
                $this->translations
            ),*/

            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s')
        ];
    }
}
