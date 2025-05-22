<?php

namespace App\Services;

use App\Models\DeviceToken;
use App\Models\User;

class DeviceTokenService
{
    public function attachToken(User $user, string $fcmToken, string $platform = 'android'): void
    {
        $device = DeviceToken::updateOrCreate(
            ['token' => $fcmToken],
            [
                'platform' => $platform,
                'last_used_at' => now()
            ]
        );

        $user->deviceTokens()->syncWithoutDetaching([$device->id]);
    }

    public function detachToken(User $user, ?string $fcmToken): void
    {
        if (!$fcmToken) return;

        $device = DeviceToken::where('token', $fcmToken)->first();

        if ($device) {
            $user->deviceTokens()->detach($device->id);

            if ($device->users()->count() === 0) {
                $device->delete();
            }
        }
    }

    public function detachAll(User $user): void
    {
        $devices = $user->deviceTokens;

        $user->deviceTokens()->detach();


        foreach ($devices as $device) {
            if ($device->users()->count() === 0) {
                $device->delete();
            }
        }
    }


    public function getDeviceCount(User $user): int
    {
        return $user->deviceTokens()->count();
    }

    public function getAllTokens(User $user): array
    {
        return $user->deviceTokens()->pluck('token')->toArray();
    }
}
