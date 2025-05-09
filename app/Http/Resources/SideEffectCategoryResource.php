<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SideEffectCategoryResource extends JsonResource
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

          //  'translations' => $this->whenLoaded('translations', $this->translations),

            'side_effects' => SideEffectResource::collection($this->whenLoaded('sideEffects')),

            // إحصائيات اختيارية
            'side_effects_count' => $this->when(
                $request->has('with_counts'),
                $this->side_effects_count ?? null
            ),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'is_popular' => $this->when(
                $request->has('with_stats'),
                function () {
                    return $this->side_effects_count > 10;
                }
            )
        ];
    }
}
