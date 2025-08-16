<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryCountDetail extends Model
{
    use HasFactory;

    /**
     * الحقول المسموح بإدخالها بشكل جماعي
     *
     * @var array
     */
    protected $fillable = [
        'inventory_count_id',
        'drug_id', // تم التعديل هنا
        'batch_id',
        'system_quantity',
        'counted_quantity',
        'reason',
    ];

    /**
     * كل سطر تفاصيل يتبع لعملية جرد رئيسية واحدة
     */
    public function inventoryCount(): BelongsTo
    {
        return $this->belongsTo(InventoryCount::class);
    }

    /**
     * كل سطر يخص دواء معين
     */
    public function drug(): BelongsTo // تم التعديل هنا
    {
        // نفترض أن لديك مودل اسمه Drug
        return $this->belongsTo(Drug::class); // تم التعديل هنا
    }

    /**
     * كل سطر يخص دفعة معينة
     */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }
}
