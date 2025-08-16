<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\DeviceToken;
use App\Services\DeviceTokenService;
use App\Services\FirebaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{

    protected $firebaseService;
    protected $deviceService;

    public function __construct(FirebaseService $firebaseService, DeviceTokenService $deviceService)
    {
        $this->firebaseService = $firebaseService;
        $this->deviceService = $deviceService;
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => [
                'required',
                'confirmed',
                Password::min(3)
            ],
            'phone_number' => 'nullable|string|max:20|unique:users,phone_number',
            'gender' => 'nullable|in:male,female',
           // 'address' => 'nullable|string|max:255',
           // 'bio' => 'nullable|string|max:500',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'phone_number' => $validated['phone_number'] ?? null,
            'gender' => $validated['gender'] ?? null,
            'role' => 'admin',
        ]);


        if (!empty($validated['fcm_token'])) {
            $this->deviceService->attachToken($user, $validated['fcm_token'], $validated['platform'] ?? 'android');
            $this->firebaseService->sendNotification(
                $validated['fcm_token'],
                'Welcome!',
                'Thank you for registering',
                ['action' => 'welcome']
            );
        }
        return response([
            'user' => $user->only(['id', 'name', 'email', 'role', 'phone_number','image']),
            'token' => $user->createToken($user->email)->plainTextToken
        ], 201);
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'fcm_token' => 'sometimes|string',
            'platform' => 'sometimes|in:android,ios,web',
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response(['message' => 'Invalid credentials.'], 403);
        }

        $user = auth()->user();

        $maxDevices = 5;
        if ($user->deviceTokens()->count() >= $maxDevices) {
            Auth::logout();
            return response(['message' => 'Maximum devices limit reached.'], 403);
        }

        if (!empty($validated['fcm_token'])) {
            $this->deviceService->attachToken($user, $validated['fcm_token'], $validated['platform'] ?? 'android');
            $this->firebaseService->sendNotification(
                $validated['fcm_token'],
                'Welcome!',
                'Thank you for logging in',
                ['action' => 'welcome']
            );
        }

        return response([
            'user' => $user->only(['id', 'name', 'email', 'role', 'phone_number','image']),
            'token' => $user->createToken($user->email)->plainTextToken
        ], 200);
    }

    public function logout(Request $request)
    {
        $user = auth()->user();
        $this->deviceService->detachToken($user, $request->input('fcm_token'));
        $user->currentAccessToken()->delete();

        return response()->noContent();
    }

    public function logoutAll()
    {
        $user = auth()->user();

        try {
            $user->update(['last_force_logout' => now()]);
            $this->sendLogoutNotification($user);
            $this->deviceService->detachAll($user);
            $this->revokeAllTokens($user);

            return response()->json([
                'message' => 'Logged out from all devices successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to logout from all devices'
            ], 500);
        }
    }

    protected function sendLogoutNotification(User $user)
    {
        $tokens = $user->deviceTokens()->pluck('token')->toArray();

        if (empty($tokens)) return;

        $this->firebaseService->sendNotification(
            $tokens,
            'Session Terminated',
            'You have been logged out from all devices',
            ['action' => 'force_logout']
        );
    }

    protected function revokeAllTokens(User $user)
    {
        $user->tokens()->delete();
        Auth::logoutOtherDevices($user->password);
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
            'email' => 'sometimes|email|unique:users,email,' . auth()->id(),
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

    public function uploadImage(Request $request, $folder)
    {
        return $request->file('image')->store($folder, 'public');
    }

    protected function revokeDeviceByToken(User $user, ?string $fcmToken): void
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
}
