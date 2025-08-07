<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    function index()
    {
        $user = User::findOrFail(auth()->id());
        // Call paginate directly on the relationship query
        $notifications = $user->notifications()->paginate(10);
        return $this->success($notifications);
    }

    /**
     * Display the specified resource.
     */
    function show($notification_id)
    {
        // Find the notification, ensuring it belongs to the authenticated user.
        $notification = auth()->user()->notifications()->findOrFail($notification_id);

        // Check if the notification is unread (pivot->read_at is null).
        if (is_null($notification->pivot->read_at)) {
            // Mark it as read by updating the pivot table.
            auth()->user()->notifications()->updateExistingPivot($notification->id, [
                'read_at' => now()
            ]);
            // Refresh the model to get the updated pivot data in the response.
            $notification->refresh();
        }

        // If the notification is found, return it.
        return $this->success($notification);
    }

    /**
     * Store a newly created resource in storage.
     */
    function store(Request $request)
    {
        // 1. Correct Validation
        $validatedData = $request->validate([
            'message' => 'required|string',
            'users'   => 'nullable|array',
            'users.*' => 'required|integer|exists:users,id', // Validate each item in the users array
        ]);

        // 2. Create the notification
        $notification = new Notification();
        $notification->message = $validatedData['message'];
        $notification->save();

        // 3. Attach users using the relationship
        if (!empty($validatedData['users'])) {
            // Attach only the specified users
            $notification->users()->attach($validatedData['users']);
        } else {
            // If no users are specified, attach all users
            $allUserIds = User::pluck('id')->all();
            $notification->users()->attach($allUserIds);
        }

        return $this->success($notification, 'Notification created successfully.');
    }

    public function unreadCount()
    {
        $count = auth()->user()->notifications()->wherePivot('read_at', null)->count();

        return $this->success(['unread_count' => $count], 'Unread notification count fetched successfully.');
    }

    public function markAsRead(Notification $notification)
    {
        // Find the specific notification for the user where it's still unread.
        $userNotification = auth()->user()->notifications()
            ->where('notification_id', $notification->id)
            ->wherePivot('read_at', null)
            ->first();

        // If it exists and is unread, update the pivot table.
        if ($userNotification) {
            auth()->user()->notifications()->updateExistingPivot($notification->id, [
                'read_at' => now()
            ]);
        }

        return $this->success(null, 'Notification marked as read.');
    }

    /**
     * Mark all unread notifications as read for the authenticated user.
     */
    public function markAllAsRead()
    {
        auth()->user()->notifications()->wherePivot('read_at', null)->update([
            'notification_user.read_at' => now()
        ]);

        return $this->success(null, 'All notifications marked as read.');
    }

}
