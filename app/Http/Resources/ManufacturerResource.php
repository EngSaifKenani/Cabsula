<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ManufacturerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->when(array_key_exists('name', $this->getAttributes()), $this->name),
            'country' => $this->when(array_key_exists('country', $this->getAttributes()), $this->country),
            'website' => $this->when(array_key_exists('website', $this->getAttributes()), $this->website),

            // العلاقات
            'drugs' => DrugResource::collection($this->whenLoaded('drugs')),
            'drugs_count' => $this->whenCounted('drugs'),

            // التواريخ
            'created_at' => $this->when(array_key_exists('created_at', $this->getAttributes()), optional($this->created_at)->format('Y-m-d H:i:s')),
            'updated_at' => $this->when(array_key_exists('updated_at', $this->getAttributes()), optional($this->updated_at)->format('Y-m-d H:i:s')),
        ];
    }
}
