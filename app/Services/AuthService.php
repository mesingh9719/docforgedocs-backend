<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    /**
     * Register a new user.
     *
     * @param array $data
     * @return array
     */
    public function __construct(
        protected Msg91Service $msg91Service
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
        try {
            // Generate a verification link (pointing to frontend)
            // Ideally, you'd generate a signed URL or a token to verify against backend.
            // For now, we'll send a simple welcome/verify link.
            // You might need to implement the actual verification API endpoint verify/{id}/{hash}

            $verificationUrl = config('app.frontend_url', 'http://localhost:5173') . '/verify-email?email=' . urlencode($user->email);

            $this->msg91Service->sendEmail(
                ['email' => $user->email, 'name' => $user->name],
                config('services.msg91.verification_template_id', 'email_verification_docforge_docs'),
                [
                    'name' => $user->name,
                    'verification_link' => $verificationUrl,
                    'year' => date('Y'),
                    'VAR1' => config('app.name', 'DocForgeDocs'),
                ]
            );
        } catch (\Exception $e) {
            // Log error but don't block registration
            \Illuminate\Support\Facades\Log::error("Failed to send verification email: " . $e->getMessage());
        }

        return [
            'user' => $user,
            'token' => $token,
        ];
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
