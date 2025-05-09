<?php

namespace App\Models;

use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SideEffect extends Model
{
    use HasFactory,HasTranslations;

    protected $translatable = ['name','category_id'];
    protected $fillable = ['name','category_id'];



    public function category()
    {
        return $this->belongsTo(SideEffectCategory::class,'category_id');
    }

    public function activeIngredients()
    {
        return $this->belongsToMany(ActiveIngredient::class, 'active_ingredient_side_effect')
            ->withTimestamps();
    }


}
