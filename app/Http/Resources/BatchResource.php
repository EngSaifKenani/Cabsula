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
            'batch_number' => $this->batch_number,
            'status' => $this->status,

            // Clarified quantity fields and corrected 'sold' logic
            'quantity' => $this->when(auth()->user()->isAdmin(), $this->quantity),
            'stock' => $this->stock,
            'sold_quantity' => $this->quantity - $this->stock,

            // Added pricing info directly
            'unit_price' => $this->unit_price,
            'unit_cost' => $this->when(auth()->user()->isAdmin(), $this->unit_cost),

            // Date fields
            'expiry_date' => $this->expiry_date?->toDateString(),
            'created_at' => $this->created_at?->toDateTimeString(),

            // NEW: Details about disposal or return actions, shown conditionally
            'action_details' => [
                'disposed_at' => $this->when($this->status == 'disposed', $this->disposed_at?->toDateTimeString()),
                'disposed_by' => $this->whenLoaded('disposer', $this->disposer ? [
                    'id' => $this->disposer->id,
                    'name' => $this->disposer->name,
                ] : null),
                'returned_at' => $this->when($this->status == 'returned', $this->returned_at?->toDateTimeString()),
                'returned_by' => $this->whenLoaded('returner', $this->returner ? [
                    'id' => $this->returner->id,
                    'name' => $this->returner->name,
                ] : null),
            ],

            // Purchase details from the original resource
            'purchase_details' => $this->whenLoaded('purchaseItem', function () {
                return [
                    'invoice_number' => $this->purchaseItem->purchaseInvoice?->invoice_number,
                    'purchase_date' => $this->purchaseItem->purchaseInvoice?->invoice_date?->toDateString(),
                    'supplier' => $this->purchaseItem->purchaseInvoice?->supplier?->name,
                ];
            }),
        ];
    }
}
