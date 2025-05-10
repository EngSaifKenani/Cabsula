<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Kreait\Firebase\Messaging\CloudMessage;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => [
                'required',
                'confirmed',
                Password::min(3)
//                    ->mixedCase()
//                    ->numbers()
            ],
            'phone_number' => 'nullable|string|max:20',
            'gender' => 'nullable|in:male,female',
            'address' => 'nullable|string|max:255',
            'bio' => 'nullable|string|max:500',
            'fcm_token' => 'sometimes|string',
            'device_id' => 'sometimes|string',
            'platform' => 'sometimes|in:android,ios,web'
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'phone_number' => $validated['phone_number'] ?? null,
            'gender' => $validated['gender'] ?? null,
            'profile_image' => $validated['profile_image'] ?? null,
            'bio' => $validated['bio'] ?? null,
            'role' => 'customer'
        ]);

        $this->handleFcmToken($user, $validated);

        session()->forget("cart{$user->id}");

        return response([
            'user' => $user->only(['id', 'name', 'email', 'role', 'phone_number']),
            'token' => $user->createToken($user->email)->plainTextToken
        ], 201);
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'fcm_token' => 'sometimes|string',
            'device_id' => 'sometimes|string',
            'platform' => 'sometimes|in:android,ios,web'
        ]);

        if (!Auth::attempt($validated)) {
            return response([
                'message' => 'Invalid credentials.'
            ], 403);
        }

        $user = auth()->user();

        // إدارة FCM Token
        $this->handleFcmToken($user, $validated);

        return response([
            'user' => $user->only(['id', 'name', 'email', 'role', 'phone_number']),
            'token' => $user->createToken($user->email)->plainTextToken
        ], 200);
    }

    public function logout()
    {
        $user = auth()->user();
        $id = $user->id;

        if (request()->has('device_id')) {
            $user->fcmTokens()
                ->where('device_id', request('device_id'))
                ->delete();
        }

        $user->currentAccessToken()->delete();

        return response()->noContent();
    }

    public function getProfile()
    {
        return response([
            'user' => auth()->user()->makeHidden(['password', 'remember_token'])
        ], 200);
    }

    public function updateProfile(Request $request)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,'.auth()->id(),
            'phone_number' => 'sometimes|string|max:20',
            'gender' => 'sometimes|in:male,female',
            'address' => 'sometimes|string|max:255',
            'profile_image' => 'sometimes|image|mimes:jpeg,png,ico,jpg,gif,svg|max:2048',
            'bio' => 'sometimes|string|max:500',
            'image' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        $user = auth()->user();
        $updateData = $validated;

        if ($request->hasFile('image')) {
            if ($user->profile_image) {
                Storage::disk('public')->delete($user->profile_image);
            }
            $updateData['profile_image'] = $this->uploadImage($request, 'profiles');
        }

        $user->update($updateData);

        return response([
            'user' => $user->fresh()->makeHidden(['password', 'remember_token']),
            'message' => 'Profile updated successfully'
        ], 200);
    }


    protected function handleFcmToken(User $user, array $data)
    {
        if (!empty($data['fcm_token'])) {
            $user->fcmTokens()->updateOrCreate(
                ['device_id' => $data['device_id'] ?? null],
                [
                    'token' => $data['fcm_token'],
                    'platform' => $data['platform'] ?? 'android'
                ]
            );
        }
    }


     function uploadImage(Request $request, $folder)
    {
        return $request->file('image')->store($folder, 'public');
    }


    public function logoutAll()
    {
        $user = auth()->user();

        try {
            $user->update(['last_force_logout' => now()]);
            $this->sendLogoutNotification($user);
            $this->revokeAllTokens($user);

            return response()->json([
                'message' => 'Logged out from all devices successfully'
            ], 200);

        } catch (\Exception $e) {
         //   Log::error('Logout all failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to logout from all devices'
            ], 500);
        }
    }
    protected function sendLogoutNotification(User $user)
    {
        if (!method_exists($user, 'fcmTokens')) return;

        $tokens = $user->fcmTokens()->pluck('token')->toArray();

        if (empty($tokens)) return;

        app('firebase.messaging')->sendMulticast(
            CloudMessage::new()
                ->withNotification([
                    'title' => 'Session Terminated',
                    'body' => 'You have been logged out from all devices'
                ])
                ->withData(['action' => 'force_logout']),
            $tokens
        );
    }

    protected function revokeAllTokens(User $user)
    {
        $user->tokens()->delete();

        if (method_exists($user, 'fcmTokens')) {
            $user->fcmTokens()->delete();
        }

        Auth::logoutOtherDevices($user->password);
    }

    // Return User Info


    // Update User Info
    public function update_profile(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'image' => 'sometimes|image|mimes:jpeg,png,ico,jpg,gif,svg|max:2048',
            'location' => 'sometimes|string|max:255',

            // 'email' => 'sometimes|email|unique:users,email,' . auth()->id(),
            // 'phone_number' => 'sometimes|digits:10|unique:users,phone_number,' . auth()->id(),
            // 'password' => 'sometimes|string|min:8|confirmed|regex:/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$/',
        ]);

        if (auth()->user()->image != null && Storage::disk('public')->exists(auth()->user()->image)) {
            Storage::disk('public')->delete(auth()->user()->image);
        }
        auth()->user()->update(
            [
                'first_name' => $request->input('first_name'),
                'last_name' => $request->input('last_name'),
                'location' => $request->input('location'),
                'image' => $this->uploadImage($request, 'profiles'),
            ]
        );

        return response([
            'user' => auth()->user(),
            'message' => 'User Updated Successfully'
        ], 200);
    }


}
