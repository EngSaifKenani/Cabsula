<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ManufacturerSalesView extends Model
{
    use HasFactory;
    protected $table = 'manufacturer_sales_view';

    public $timestamps = false;

    protected $guarded = [];
}
