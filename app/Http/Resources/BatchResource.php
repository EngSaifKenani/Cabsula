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
            'status' => $this->when(auth()->user()->isAdmin()||auth()->user()->isPharmacist(), $this->status),
            'sold' => $this->stock-$this->quantity,
            'stock' => $this->stock,
            'quantity' => $this->when(auth()->user()->isAdmin(), $this->quantity),
            'production_date' => $this->production_date?->toDateString(),
            'expiry_date' => $this->expiry_date?->toDateString(),
            'created_at' => $this->created_at?->toDateTimeString(),

            'purchase_details' => $this->whenLoaded('purchaseItem', function () {
                return [
                    'cost_price' => $this->purchaseItem->cost_price,
                    'quantity_purchased' => $this->purchaseItem->quantity,
                    'invoice' => $this->purchaseItem->purchaseInvoice ? [
                        'invoice_number' => $this->purchaseItem->purchaseInvoice->invoice_number,
                        'supplier' => $this->purchaseItem->purchaseInvoice->supplier,
                        'purchase_date' => $this->purchaseItem->purchaseInvoice->purchase_date?->toDateString(),
                    ] : null,
                ];
            }),

        ];    }
}
