<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FCMToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'token',
        'device_id',
        'platform',
        'device_name',
        'device_model',
        'os_version'
    ];

    protected $casts = [
        'last_used_at' => 'datetime'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive($query)
    {
        return $query->whereNotNull('token');
    }

    public function scopeForPlatform($query, $platform)
    {
        return $query->where('platform', $platform);
    }

    public function markAsUsed()
    {
        $this->update(['last_used_at' => now()]);
        return $this;
    }
}
