<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationUser extends Model
{
    protected $fillable=['notification_id','user_id','isRead'];
    use HasFactory;
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function notification()
    {
        return $this->belongsTo(Notification::class);
    }
}
