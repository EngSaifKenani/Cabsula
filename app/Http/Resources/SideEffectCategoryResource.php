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
            'name' => $this->when(array_key_exists('name', $this->getAttributes()), $this->name),

            // الترجمة - إذا تم تحميلها
            //'translations' => $this->whenLoaded('translations', $this->translations),

            'side_effects' => SideEffectResource::collection(
                $this->whenLoaded('sideEffects')
            ),

            // إحصائيات اختيارية
            'side_effects_count' => $this->when(
                $request->has('with_counts'),
                $this->when(array_key_exists('side_effects_count', $this->getAttributes()), $this->side_effects_count ?? null)
            ),

            'created_at' => $this->when(
                array_key_exists('created_at', $this->getAttributes()),
                optional($this->created_at)->format('Y-m-d H:i:s')
            ),
            'updated_at' => $this->when(
                array_key_exists('updated_at', $this->getAttributes()),
                optional($this->updated_at)->format('Y-m-d H:i:s')
            ),

            'is_popular' => $this->when(
                $request->has('with_stats'),
                function () {
                    return $this->side_effects_count > 10;
                }
            )
        ];
    }
}
