<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Batch extends Model
{
    protected $fillable = [
        'drug_id',
        'status',
        'price',
        'cost',
        'stock',
        'quantity',
        'production_date',
        'expiry_date',
    ];
    protected $dates = [
        'production_date',
        'expiry_date',
    ];
    protected $casts = [
        'production_date' => 'date',
        'expiry_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    use HasFactory;

    public function drug()
    {
        return $this->belongsTo(Drug::class);
    }
}
