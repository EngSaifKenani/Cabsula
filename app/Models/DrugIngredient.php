<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DrugIngredient extends Model
{
    use HasFactory;
    protected $table = 'drug_ingredients';

    protected $fillable = [
        'drug_id',
        'active_ingredient_id',
        'concentration_id',
        'amount_per_unit'
    ];

    public function drug()
    {
        return $this->belongsTo(Drug::class);
    }

    public function activeIngredient()
    {
        return $this->belongsTo(ActiveIngredient::class);
    }

    public function concentration()
    {
        return $this->belongsTo(Concentration::class);
    }
}
