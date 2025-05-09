<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class ActiveIngredientTherapeuticUse extends Pivot
{
    protected $table = 'active_ingredient_therapeutic_use';

     public $timestamps = true;

     protected $fillable = [
        'active_ingredient_id',
        'therapeutic_use_id',
         'is_primary'
    ];
}
