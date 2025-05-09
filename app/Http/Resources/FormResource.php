<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FormResource extends JsonResource
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
            'image_url' => $this->image ? asset('storage/'.$this->image) : null,

//            'translations' => $this->whenLoaded('translations', $this->translations),

            'drugs' => DrugResource::collection($this->whenLoaded('drugs')),
            'drugs_count' => $this->whenCounted('drugs'),


            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
