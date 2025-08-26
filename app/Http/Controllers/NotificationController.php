<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Get all notifications for the authenticated user.
     */
    public function index()
    {
        $notifications = auth()->user()->notifications()->latest()->paginate(10);

        return $this->success($notifications);
    }

    /**
     * Mark a specific notification as read and show it.
     */
    function show(Notification $notification)
    {
        // 1. استخدام علاقة المستخدم للبحث عن الإشعار.
        // `findOrFail` سيبحث عن الإشعار المحدد فقط ضمن إشعارات المستخدم الحالي.
        // إذا لم يجده، سيعيد تلقائيًا خطأ 404 (Not Found).
        $userNotification = auth()->user()->notifications()->findOrFail($notification->id);

        // 2. إذا كان الإشعار موجودًا وغير مقروء، قم بتحديثه.
        if (is_null($userNotification->pivot->read_at)) {
            auth()->user()->notifications()->updateExistingPivot($userNotification->id, [
                'read_at' => now(),
            ]);
        }

        return $this->success($userNotification);
    }

    /**
     * Create a new notification and attach it to users.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'message' => 'required|string',
            'users' => 'nullable|array',
            'users.*' => 'required|integer|exists:users,id',
        ]);

        // Wrap the creation and attachment in a database transaction.
        // This ensures atomicity: either both actions succeed, or neither do.
        $notification = \DB::transaction(function () use ($validated) {
            $notification = Notification::create(['message' => $validated['message']]);

            $usersToNotify = $validated['users'] ?? User::pluck('id');
            $notification->users()->attach($usersToNotify);

            return $notification;
        });

        return $this->success($notification, 'Notification created successfully.');
    }

    /**
     * Get the count of unread notifications for the authenticated user.
     */
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

    /**
     * Mark all unread notifications as read.
     */
    public function markAllAsRead()
    {
        auth()->user()->notifications()->newPivotStatement()->whereNull('read_at')->update(['read_at' => now()]);

        return $this->success(null, 'All notifications marked as read.');
    }
}
