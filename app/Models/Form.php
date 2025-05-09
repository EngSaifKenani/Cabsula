<?php

namespace App\Models;

use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Form extends Model
{
    use HasFactory,HasTranslations;

    protected $translatable = ['name','description'];
    protected $fillable = ['name','description','image'];


    public function drugs()
    {
        return $this->hasMany(Drug::class);
    }

}
