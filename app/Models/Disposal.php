<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Disposal extends Model
{
    use HasFactory;

    protected $fillable = [
        'batch_id',
        'user_id',
        'disposed_quantity',
        'loss_amount',
        'reason',
        'disposed_at',
    ];

    protected $casts = [
        'disposed_at' => 'datetime',
    ];

    /**
     * Get the batch that was disposed of.
     */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    /**
     * Get the user who performed the disposal.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
