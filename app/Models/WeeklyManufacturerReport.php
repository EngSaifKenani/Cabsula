<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WeeklyManufacturerReport extends Model
{
    use HasFactory;
    protected $fillable = [
        'manufacturer_id',
        'manufacturer_name',
        'year',
        'week',
        'total_quantity_sold',
        'total_profit',
    ];
    public function manufacturer()
    {
        return $this->belongsTo(Manufacturer::class);
    }

}
