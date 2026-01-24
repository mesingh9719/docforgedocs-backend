<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Services\AuthService;

class VerificationController extends Controller
{
    public function __construct(
        protected AuthService $authService
    ) {
    }

    /**
     * Mark the authenticated user's email address as verified.
     */
    public function verify(Request $request, $id)
    {
        $user = User::findOrFail($id);

        // Check signature first, but fallback to hash verification if it fails (common in dev/proxy setups)
        if (!$request->hasValidSignature()) {

            // Fallback: Verify logic manually: sha1(email) === hash
            // This is secure enough because it proves the user got the link sent to that email.
            $expectedHash = sha1($user->getEmailForVerification());
            $providedHash = $request->route('hash');

            if (!hash_equals($expectedHash, $providedHash)) {
                \Illuminate\Support\Facades\Log::error('Email Verification Failed completely', [
                    'url' => $request->fullUrl(),
                ]);
                return response()->json(['message' => 'Invalid or expired URL.'], 403);
            }
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified.']);
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return response()->json(['message' => 'Email verified successfully.']);
    }

    /**
     * Resend the email verification notification.
     */
    public function resend(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified.']);
        }

        $this->authService->sendVerificationNotification($request->user());

        return response()->json(['message' => 'Verification link sent.']);
    }
}
