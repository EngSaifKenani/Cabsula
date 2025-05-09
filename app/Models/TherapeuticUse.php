<?php

namespace App\Models;

use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TherapeuticUse extends Model
{
    use HasFactory;
    use HasTranslations;
    protected $translatable = ['name', 'description'];
    protected $with = ['translations'];
    protected $fillable = ['name', 'description', 'image'];

    public function activeIngredients()
    {
        return $this->belongsToMany(ActiveIngredient::class)
            ->using(ActiveIngredientTherapeuticUse::class)
            ->withPivot([ 'is_popular'])
            ->withTimestamps();
    }

}
