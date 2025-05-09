<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;

class AuthController extends Controller
{
    // Register New User
    public function register(Request $request)
    {

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:3|confirmed' //|regex:/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$/
        ]);

        $user = User::create([
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'password' => Hash::make($request->input('password')),
        ]);
        session()->forget("cart{$user->id}");
        return response([
            'user' => $user,
            'token' => $user->createToken($user->email)->plainTextToken
        ], 201);


    }

    // Login
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:3' //|regex:/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$/

        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response([
                'message' => 'invalid credentials.'
            ], 403);
        }
//        $id = Auth::user()->id;
//        session()->forget("cart{$id}");

        return response([
            'user' => auth()->user(),
            'token' => auth()->user()->createToken(auth()->user()->email)->plainTextToken
        ], 200);
    }

    // Logout
    public function logout()
    {
        // Delete The Current Token Only
        $id = auth()->user()->id;
        auth()->user()->currentAccessToken()->delete();


        session()->forget("cart{$id}");
        return response(status: 204);

    }

/*    public function logoutAll()
    {
        // Delete The All Token Only
        $id = auth()->user()->id;
        auth()->user()->tokens()->delete();
        auth()->user()->deviceTokens()->delete();
        Session::forget("cart{$id}");
        return response(status: 204);

    }*/



    // Return User Info
    public function get_profile()
    {
        return response([
            'user' => auth()->user()
        ], 200);

    }


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
