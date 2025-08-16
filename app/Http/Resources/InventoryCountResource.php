<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryCountResource extends JsonResource
{
    /**
     * تحويل الريسورس إلى مصفوفة.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'count_date' => $this->count_date->toDateTimeString(),
            'notes' => $this->notes,
            'created_at' => $this->created_at->toDateTimeString(),

            'admin' => new UserResource($this->whenLoaded('admin')), // افترض أن لديك UserResource
            'details' => InventoryCountDetailResource::collection($this->whenLoaded('details')), // افترض أن لديك InventoryCountDetailResource
        ];
    }
}
