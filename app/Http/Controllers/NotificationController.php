<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{

    public function index()
    {
        $notifications = auth()->user()->notifications()->latest()->paginate(10);

        return $this->success($notifications);
    }

    public function show(Notification $notification)
    {
        $userNotification = auth()->user()->notifications()->where('notification_id', $notification->id)->firstOrFail();

        if (is_null($userNotification->pivot->read_at)) {
            $userNotification->pivot->read_at = now();
            $userNotification->pivot->save();
        }

        return $this->success($userNotification);
    }


    public function store(Request $request)
    {
        $validated = $request->validate([
            'message' => 'required|string',
            'users' => 'nullable|array',
            'users.*' => 'required|integer|exists:users,id',
        ]);

       $notification = DB::transaction(function () use ($validated) {
            $notification = Notification::create(['message' => $validated['message']]);

            $usersToNotify = $validated['users'] ?? User::pluck('id');
            $notification->users()->attach($usersToNotify);

            return $notification;
        });

        return $this->success($notification, 'Notification created successfully.');
    }

    public function unreadCount()
    {
        $count = auth()->user()->notifications()->wherePivotNull('read_at')->count();

        return $this->success(['unread_count' => $count], 'Unread notification count fetched successfully.');
    }

    /**
     * Mark a specific notification as read.
     */
    public function markAsRead(Notification $notification)
    {
        auth()->user()->notifications()->updateExistingPivot($notification->id, [
            'read_at' => now(),
        ]);

        return $this->success(null, 'Notification marked as read.');
    }

    public function markAllAsRead()
    {
        auth()->user()->notifications()->newPivotStatement()->whereNull('read_at')->update(['read_at' => now()]);

        return $this->success(null, 'All notifications marked as read.');
    }
}
