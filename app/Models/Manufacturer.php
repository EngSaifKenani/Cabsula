<?php

namespace App\Models;

use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Manufacturer extends Model
{
    use HasFactory;
    use HasTranslations;
    protected $translatable = ['name','country'];
    protected $fillable = ['name','website','country'];

    public function drugs()
    {
        return $this->hasMany(Drug::class);
    }

}
