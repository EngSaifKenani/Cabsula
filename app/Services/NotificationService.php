<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class NotificationService
{
    /**
     * Create a new notification and send it to specified users or all users.
     *
     * @param string $message
     * @param string $type
     * @param array|null $userIds
     * @return Notification
     */
    public function createAndSendNotification(string $message, string $type, ?array $userIds = null): Notification
    {
        return DB::transaction(function () use ($message, $type, $userIds) {
            // 1. Create the notification record
            $notification = new Notification();
            $notification->message = $message;
            $notification->type = $type; // <-- تم إضافة حقل النوع هنا
            $notification->save();

            // 2. Attach users based on the provided IDs or all users if none provided
            if ($userIds) {
                $notification->users()->attach($userIds);
            } else {
                $allUserIds = User::pluck('id');
                $notification->users()->attach($allUserIds);
            }

            // مثال: Notification::send($users, new App\Notifications\NewNotification($message));

            return $notification;
        });
    }
}
