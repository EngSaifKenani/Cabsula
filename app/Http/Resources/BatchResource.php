<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BatchResource extends JsonResource
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
            'batch_number'=>$this->batch_number,
            //  'drug_id' => $this->drug_id,
            'drug_name' => $this->whenLoaded('drug')->name ?? null,
            'status' => $this->when(auth()->user()->isAdmin()||auth()->user()->isPharmacist(), $this->status),
            'sold' => $this->when(auth()->user()->isAdmin(), $this->sold),
            'price' => $this->price,
            'cost' => $this->when(auth()->user()->isAdmin(), $this->cost),
            'stock' => $this->stock,
            'quantity' => $this->when(auth()->user()->isAdmin(), $this->quantity),
            'production_date' => $this->production_date?->toDateString(),
            'expiry_date' => $this->expiry_date?->toDateString(),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];    }
}
