<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeviceToken extends Model
{
    use HasFactory;
    protected $fillable=['token','platform','last_used_at'];

    public function users()
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

}
