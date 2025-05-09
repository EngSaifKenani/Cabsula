<?php

namespace App\Models;

use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecommendedDosage extends Model
{
    use HasFactory,HasTranslations;

    protected $translatable = ['dosage','notes'];
    protected $fillable = [
        'dosage',
        'notes'
    ];


    public function drugs()
    {
        return $this->hasMany(Drug::class,'recommended_dosage_id');
    }


}
