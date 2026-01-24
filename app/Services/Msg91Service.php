<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Msg91Service
{
    protected $baseUrl = 'https://control.msg91.com/api/v5/email/send';
    protected $authKey;
    protected $domain;
    protected $fromEmail;

    public function __construct()
    {
        $this->authKey = config('services.msg91.key');
        $this->domain = config('services.msg91.domain');
        $this->fromEmail = config('services.msg91.from_email');
    }

    /**
     * Send an email using MSG91 V5 API.
     *
     * @param string|array $to Recipient email or array of ['email' => '...', 'name' => '...']
     * @param string $templateId The Template ID from MSG91
     * @param array $variables Key-value pairs of variables to replace in the template
     * @return array
     */
    public function sendEmail($to, string $templateId, array $variables = [])
    {
        // 1. Normalize 'to' into a strict list of objects: [['email' => '...', 'name' => '...']]
        $normalizedTo = [];

        if (is_string($to)) {
            $normalizedTo[] = ['email' => $to];
        } elseif (is_array($to)) {
            // Check if it's a single associative array: ['email' => 'foo@bar.com']
            if (isset($to['email'])) {
                $normalizedTo[] = $to;
            } else {
                // Check if it's an indexed array of recipients (or strings)
                // We'll iterate to be safe
                foreach ($to as $recipient) {
                    if (is_string($recipient)) {
                        $normalizedTo[] = ['email' => $recipient];
                    } elseif (is_array($recipient) && isset($recipient['email'])) {
                        $normalizedTo[] = $recipient;
                    }
                }
            }
        }

        if (empty($normalizedTo)) {
            Log::error('MSG91 Email Error: No valid recipients provided.');
            return ['success' => false, 'error' => 'No valid recipients'];
        }

        if (empty($this->authKey)) {
            Log::error('MSG91 Error: Auth Key is missing in configuration.');
            return ['success' => false, 'error' => 'Auth Key missing'];
        }

        // 2. Construct Payload strictly following MSG91 v5 recommended structure
        // "recipients": [ { "to": [...], "variables": {...} } ]
        $recipientsPayload = [
            [
                'to' => $normalizedTo,
                'variables' => $variables
            ]
        ];

        $payload = [
            'recipients' => $recipientsPayload,
            'from' => [
                'name' => config('app.name', 'DocForgeDocs'),
                'email' => $this->fromEmail
            ],
            'domain' => $this->domain,
            'template_id' => $templateId
        ];

        // Log the payload for debugging (optional, remove in high volume prod if needed)
        Log::info('MSG91 Payload:', $payload);

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'authkey' => $this->authKey,
            ])->post($this->baseUrl, $payload);

            if ($response->successful()) {
                Log::info('MSG91 Email Sent: ' . $response->body());
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            Log::error('MSG91 Email Failed: ' . $response->body());
            return [
                'success' => false,
                'error' => $response->body()
            ];

        } catch (\Exception $e) {
            Log::error('MSG91 Email Exception: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
