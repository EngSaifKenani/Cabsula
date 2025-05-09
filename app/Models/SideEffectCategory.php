<?php

namespace App\Models;

use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SideEffectCategory extends Model
{
    use HasFactory,HasTranslations;

    protected $translatable = ['name'];
    protected $fillable = ['name'];

    public function sideEffects()
    {
        return $this->hasMany(SideEffect::class, 'category_id');
    }

}
