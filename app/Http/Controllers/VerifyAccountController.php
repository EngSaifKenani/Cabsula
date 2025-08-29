<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class VerifyAccountController extends Controller
{
    public function sendCodeToEmail(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email'
        ]);

        $email = $validated['email'];
        $code = strtoupper(Str::random(8));
        $cacheKey = 'verification_code_' . md5($email);

        if (Cache::has($cacheKey)) {
            return response()->json(['message' => 'You’ve already requested a code recently. Please wait before trying again'], 429);
        }

        Cache::put($cacheKey, $code, now()->addMinutes(15));

        try {
            Mail::raw("the code is : {$code}", function ($message) use ($email) {
                $message->to($email)
                    ->subject('Verify Code !' . config('app.name'));
            });
        } catch (\Exception $e) {
            return response()->json(['message' =>  $e->getMessage()], 500);
        }

        return response()->json(['message' => 'code send successfully'], 200);
    }
}
