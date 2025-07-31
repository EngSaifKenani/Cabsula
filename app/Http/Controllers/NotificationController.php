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
        // This single line handles both finding the notification and authorization.
        // It searches for the notification ONLY within the collection belonging to the authenticated user.
        // If it's not found (either because it doesn't exist or doesn't belong to the user),
        // Laravel will automatically return a 404 Not Found response, which acts as a "forbidden" response.
        $notification = auth()->user()->notifications()->findOrFail($notification_id);

        // If the notification is found, return it using your existing success method.
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

}
