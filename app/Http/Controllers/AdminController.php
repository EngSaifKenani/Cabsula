<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    public function listPharmacists()
    {
        $pharmacists = User::where('role', 'pharmacist')->get();
        return $this->success($pharmacists);
    }

    public function getPharmacistById($id)
    {
        $pharmacist = User::where('id', $id)
            ->where('role', 'pharmacist')
            ->select(['id', 'name', 'email', 'phone_number', 'address', 'created_at'])
            ->first();

        if (!$pharmacist) {
            return $this->error('The specified user is not a pharmacist', 400);
        }

        return $this->success($pharmacist);
    }

    public function createPharmacist(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:3|confirmed',
         //   'phone_number' => 'required|string|max:20',
          //  'address' => 'required|string|max:500'
        ]);

        $user = User::create([
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'password' => Hash::make($request->input('password')),
            'role' => 'pharmacist',
          //  'phone_number' => $request->input('phone_number'),
           // 'address' => $request->input('address')
        ]);

        return $this->success([
            'user' => $user,
            'token' => $user->createToken($user->email)->plainTextToken
        ], 201);
    }

    public function updatePharmacist(Request $request, $id)
    {
        $pharmacist=User::findOrFail($id);

        if ($pharmacist->role !== 'pharmacist') {
            return $this->error('The specified user is not a pharmacist', 400);
        }

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => [
                'sometimes',
                'email',
                Rule::unique('users')->ignore($pharmacist->id)
            ],            'phone_number' => 'sometimes|string|max:20',
            'address' => 'sometimes|string|max:500'
        ]);

        $pharmacist->update($request->only([
            'name', 'email', 'phone_number', 'address'
        ]));

        return $this->success($pharmacist);
    }



    public function deletePharmacist($id)
    {
        $pharmacist=User::findOrFail($id);
        if ($pharmacist->role !== 'pharmacist') {
            return $this->error('The specified user is not a pharmacist', 400);
        }

        $pharmacist->delete();
        return $this->success([], 'Pharmacist deleted successfully');
    }
}
