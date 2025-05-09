<?php

namespace App\Models;

use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ActiveIngredient extends Model
{
    use HasFactory , HasTranslations,SoftDeletes;
    public function translations()
    {
        return $this->morphMany(Translation::class, 'translatable');
    }

    protected $fillable = [
        'scientific_name',
        'description',
        'cas_number',
        'unii_code',
        'is_active'
    ];

    protected $translatable = ['scientific_name',
        'description',];


    protected $casts = [
        'is_active' => 'boolean'
    ];


    public function drugs()
    {
        return $this->belongsToMany(Drug::class,'drug_ingredients')
            ->withPivot('concentration', 'unit')
            ->withTimestamps(false, false);
    }


    public function sideEffects()
    {
        return $this->belongsToMany(SideEffect::class, 'active_ingredient_side_effect')
            ->withTimestamps();
    }

    public function therapeuticUses()
    {
        return $this->belongsToMany(TherapeuticUse::class)
            ->using(ActiveIngredientTherapeuticUse::class)
            ->withPivot([ 'is_popular'])
            ->withTimestamps();
    }


    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }




}
