<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryCountDetailResource extends JsonResource
{
    /**
     * تحويل الريسورس إلى مصفوفة.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $discrepancy = $this->counted_quantity - $this->system_quantity;

        return [
            'id' => $this->id,
            'system_quantity' => $this->system_quantity,
            'counted_quantity' => $this->counted_quantity,
            'discrepancy' => $discrepancy, // حقل محسوب للفرق
            'reason' => $this->reason,

            'drug' => new DrugResource($this->whenLoaded('drug')), // افترض أن لديك DrugResource
            'batch' => new BatchResource($this->whenLoaded('batch')), // افترض أن لديك BatchResource
        ];
    }
}
