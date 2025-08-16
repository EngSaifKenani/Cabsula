<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone_number',
        'gender',
        'bio',
        'address',
        'role',
        'last_force_logout',
        'email_verified_at',
        'image'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_force_logout' => 'datetime',
    ];

    // العلاقات
    public function deviceTokens()
    {
        return $this->belongsToMany(DeviceToken::class)->withTimestamps();
    }
    public function notifications()
    {
        return $this->belongsToMany(Notification::class, 'notification_user')
            // Tell Eloquent to also retrieve the 'read_at' column from the pivot table.
            ->withPivot('read_at')
            // This is good practice to automatically manage created_at/updated_at on the pivot table.
            ->withTimestamps()
            // Order by the newest notifications first.
            ->orderByPivot('created_at', 'desc');
    }




    public function scopePharmacists($query)
    {
        return $query->where('role', 'pharmacist');
    }

    public function scopeAdmins($query)
    {
        return $query->where('role', 'admin');
    }

    public function scopeCustomers($query)
    {
        return $query->where('role', 'customer');
    }

    // دوال مساعدة
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isPharmacist(): bool
    {
        return $this->role === 'pharmacist';
    }

    public function isCustomer(): bool
    {
        return $this->role === 'customer';
    }

    public function forceLogout()
    {
        $this->update(['last_force_logout' => now()]);
        $this->tokens()->delete();
        $this->fcmTokens()->delete();
    }

    public function isAvailableAt($datetime)
    {
        if (!$this->isPharmacist()) return false;

        $day = strtolower($datetime->format('l'));
        $time = $datetime->format('H:i:s');

        // التحقق من أوقات الدوام
        $onSchedule = $this->schedules()
            ->where('day', $day)
            ->where('is_working', true)
            ->where('start_time', '<=', $time)
            ->where('end_time', '>=', $time)
            ->exists();

        // التحقق من العطلات
        $onVacation = $this->vacations()
            ->whereDate('start_date', '<=', $datetime)
            ->whereDate('end_date', '>=', $datetime)
            ->exists();

        return $onSchedule && !$onVacation;
    }

    public function addFcmToken($token, $deviceId = null, $platform = 'android')
    {
        return $this->fcmTokens()->updateOrCreate(
            ['device_id' => $deviceId],
            ['token' => $token, 'platform' => $platform]
        );
    }
}
