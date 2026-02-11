<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RecaptchaService
{
    public function verify($token)
    {
        $secret = config('services.recaptcha.secret');
        Log::info('Recaptcha verification attempt', ['secret_configured' => !empty($secret)]);

        $response = Http::asForm()->post(
            'https://www.google.com/recaptcha/api/siteverify',
            [
                'secret' => $secret,
                'response' => $token,
            ]
        );

        $result = $response->json();
        Log::info('Recaptcha API response', $result);

        return $result;
    }
}
