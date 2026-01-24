<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

use Illuminate\Support\Facades\URL;

class AuthService
{
    /**
     * Create a new service instance.
     *
     * @param NotificationService $notificationService
     */
    public function __construct(
        protected NotificationService $notificationService
    ) {
    }

    /**
     * Register a new user.
     *
     * @param array $data
     * @return array
     */
    public function register(array $data): array
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        // Send Verification Email
        $this->sendVerificationNotification($user);

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    /**
     * Send the email verification notification.
     *
     * @param User $user
     * @return void
     */
    public function sendVerificationNotification(User $user): void
    {
        try {
            // Ensure secure URL generation for production
            if (app()->environment('production')) {
                URL::forceScheme('https');
            }

            // Generate a signed URL for the backend verification route
            $backendUrl = URL::temporarySignedRoute(
                'verification.verify',
                now()->addMinutes(60),
                ['id' => $user->id, 'hash' => sha1($user->getEmailForVerification())]
            );

            // Construct the frontend URL with the backend URL as a parameter
            $verificationUrl = config('app.frontend_url', 'http://localhost:5173') . '/verify-email?verify_url=' . urlencode($backendUrl);

            $this->notificationService->sendVerificationEmail($user, $verificationUrl);

        } catch (\Exception $e) {
            // Log error but don't block registration
            \Illuminate\Support\Facades\Log::error("Failed to send verification email: " . $e->getMessage());
        }
    }



    /**
     * Login a user.
     *
     * @param array $data
     * @return array
     */
    public function login(array $data): array
    {
        $user = User::where('email', $data['email'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials do not match our records.'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    /**
     * Logout a user.
     *
     * @param \App\Models\User $user
     * @return void
     */
    public function logout(User $user): void
    {
        $user->currentAccessToken()->delete();
    }
}
