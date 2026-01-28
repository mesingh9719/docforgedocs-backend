<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();

            $user = User::where('email', $googleUser->getEmail())->first();

            if (!$user) {
                // Register new user
                $user = User::create([
                    'name' => $googleUser->getName(),
                    'email' => $googleUser->getEmail(),
                    'password' => Hash::make(Str::random(24)), // Random password
                    'google_id' => $googleUser->getId(),
                    'email_verified_at' => now(), // Auto verify for Social Login
                ]);
            } else {
                // Update google_id if missing
                if (!$user->google_id) {
                    $user->update(['google_id' => $googleUser->getId()]);
                }
            }

            // Create Token
            $token = $user->createToken('auth_token')->plainTextToken;

            // Redirect to Frontend with Token
            $frontendUrl = config('services.frontend_url', 'http://localhost:5173');
            return redirect("{$frontendUrl}/auth/callback?token={$token}");

        } catch (\Exception $e) {
            return redirect(config('services.frontend_url') . '/login?error=Google login failed');
        }
    }
}
