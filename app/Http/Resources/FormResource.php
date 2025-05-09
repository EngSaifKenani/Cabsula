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
            'name' => $this->when(array_key_exists('name', $this->getAttributes()), $this->name),
            'description' => $this->when(array_key_exists('description', $this->getAttributes()), $this->description),
            'image_url' => $this->when(array_key_exists('image', $this->getAttributes()), $this->image ? asset('storage/'.$this->image) : null),

            // العلاقات
            'drugs' => DrugResource::collection($this->whenLoaded('drugs')),
            'drugs_count' => $this->whenCounted('drugs'),

            // التواريخ
            'created_at' => $this->when(array_key_exists('created_at', $this->getAttributes()), optional($this->created_at)->format('Y-m-d H:i:s')),
            'updated_at' => $this->when(array_key_exists('updated_at', $this->getAttributes()), optional($this->updated_at)->format('Y-m-d H:i:s')),
        ];
    }
}
