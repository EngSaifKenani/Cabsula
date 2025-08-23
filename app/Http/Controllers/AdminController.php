<?php

namespace App\Http\Controllers;

use App\Jobs\SendNotificationJob;
use App\Models\User;
use App\Services\FirebaseService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage; // استيراد للتعامل مع الملفات
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    public function __construct(NotificationService $notificationService, FirebaseService $firebaseService)
    {
        $this->notificationService = $notificationService;
        $this->firebaseService = $firebaseService;
    }
    public function listPharmacists()
    {
        $pharmacists = User::where('role', 'pharmacist')->get();
        return $this->success($pharmacists);
    }

    public function getPharmacistById($id)
    {
        $pharmacist = User::where('id', $id)
            ->where('role', 'pharmacist')
            ->select(['id', 'name', 'email', 'phone_number', 'address', 'created_at', 'image'])
            ->first();

        if (!$pharmacist) {
            return $this->error('The specified user is not a pharmacist', 400);
        }

        if ($pharmacist->image) {
            $pharmacist->image = asset('storage/' . $pharmacist->image);
        }

        return $this->success($pharmacist);
    }

    public function createPharmacist(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:3',
            'phone_number' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('pharmacist_images', 'public');
            $validatedData['image'] = $imagePath;
        }

        $validatedData['password'] = Hash::make($request->input('password'));
        $validatedData['role'] = 'pharmacist';

        $user = User::create($validatedData);

        $usersToNotify = User::whereIn('role', ['admin','pharmacist'])->get();
        $deviceTokens = $usersToNotify->flatMap(function ($user) {
            return $user->deviceTokens->pluck('token');
        })->filter()->toArray();
        $userIds = $usersToNotify->pluck('id')->toArray();

        $message = "تمت إضافة صيدلي جديد: {$user->name}.";
        $type = 'general';
        $title = 'صيدلي جديد!';

        SendNotificationJob::dispatch($message, $type, $title, $userIds, $deviceTokens);


        return $this->success([
            'user' => $user,
            'token' => $user->createToken($user->email)->plainTextToken
        ], 201);
    }

    public function updatePharmacist(Request $request, $id)
    {
        $pharmacist = User::findOrFail($id);

        if ($pharmacist->role !== 'pharmacist') {
            return $this->error('The specified user is not a pharmacist', 400);
        }

        $validatedData = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => [
                'sometimes',
                'email',
                Rule::unique('users')->ignore($pharmacist->id)
            ],
            'phone_number' => 'sometimes|string|max:20',
            'address' => 'sometimes|string|max:500',
            'password' => 'sometimes|string|min:3',
            'image' => 'sometimes|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($request->hasFile('image')) {
            if ($pharmacist->image) {
                Storage::disk('public')->delete($pharmacist->image);
            }
            $imagePath = $request->file('image')->store('pharmacist_images', 'public');
            $validatedData['image'] = $imagePath;
        }


        if ($request->filled('password')) {
            $validatedData['password'] = Hash::make($request->input('password'));
        }

        $pharmacist->update($validatedData);

        return $this->success($pharmacist);
    }

    public function deletePharmacist($id)
    {
        $pharmacist = User::findOrFail($id);
        if ($pharmacist->role !== 'pharmacist') {
            return $this->error('The specified user is not a pharmacist', 400);
        }

        if ($pharmacist->image) {
            Storage::disk('public')->delete($pharmacist->image);
        }

        $pharmacist->delete();
        return $this->success([], 'Pharmacist deleted successfully');
    }
}
