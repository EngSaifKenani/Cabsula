<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Batch extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'purchase_item_id',
        'drug_id',
        'batch_number',
        'quantity',
        'stock',
        'expiry_date',
        'unit_cost',
        'unit_price',
        'is_expiry_notified',
        'total',
        'status',
        ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'expiry_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'invoice_date' => 'date',

    ];

    /**
     * Get the purchase item that this batch belongs to.
     */
    public function purchaseItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseItem::class);
    }

    /**
     * Get the drug for this batch.
     */
    public function drug(): BelongsTo
    {
        return $this->belongsTo(Drug::class);
    }
    public function disposer()
    {
        return $this->belongsTo(User::class, 'disposed_by');
    }
    public function returner()
    {
        return $this->belongsTo(User::class, 'returned_by');
    }
}
