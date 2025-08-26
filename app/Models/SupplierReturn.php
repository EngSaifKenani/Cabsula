<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierReturn extends Model
{
    use HasFactory;

    protected $fillable = [
        'batch_id',
        'supplier_id',
        'purchase_invoice_id',
        'user_id',
        'returned_quantity',
        'credit_amount',
        'notes',
    ];

    /**
     * Get the batch that was returned.
     */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    /**
     * Get the supplier to whom the items were returned.
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Get the original purchase invoice for the returned items.
     */
    public function purchaseInvoice(): BelongsTo
    {
        return $this->belongsTo(PurchaseInvoice::class);
    }

    /**
     * Get the user who performed the return.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
