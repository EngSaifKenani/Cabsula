<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryCount extends Model
{
    use HasFactory;

    /**
     * الحقول المسموح بإدخالها بشكل جماعي
     *
     * @var array
     */
    protected $fillable = [
        'count_date',
        'admin_id',
        'notes',
    ];

    protected $casts = [
        'count_date' => 'datetime',
    ];

    /**
     * عملية الجرد الواحدة تحتوي على العديد من التفاصيل
     */
    public function details(): HasMany
    {
        return $this->hasMany(InventoryCountDetail::class);
    }

    /**
     * عملية الجرد الواحدة قام بها موظف واحد
     */
    public function admin(): BelongsTo
    {
        // نفترض أن المودل الخاص بالموظفين هو User
        return $this->belongsTo(User::class, 'admin_id');
    }
}
