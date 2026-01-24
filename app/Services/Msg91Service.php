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
        // Format recipients
        $recipients = [];
        if (is_string($to)) {
            $recipients[] = [
                'to' => [
                    ['email' => $to]
                ],
                'variables' => $variables
            ];
        } elseif (isset($to['email'])) {
            // Single recipient object
            $recipients[] = [
                'to' => [$to],
                'variables' => $variables
            ];
        } else {
            // Assume strict structure or handle array of emails
            // For simplicity, let's assume strict structure as per docs or auto-format
            // Current doc example structure:
            // "recipients": [ { "to": [ {"email": "...", "name": "..."} ], "variables": {...} } ]

            // If user passes direct email string, we wrapped it above.
            // If user passes multiple recipients with distinct variables, they should construct that array.
            // Here we handle the simple case: One main recipient set with one set of variables.
            $recipients[] = [
                'to' => is_array($to) && isset($to[0]['email']) ? $to : [['email' => $to]], // Fallback
                'variables' => $variables
            ];
        }

        // Fix: If the $to argument was already the full 'to' array structure
        if (is_array($to) && isset($to[0]['email'])) {
            $recipients = [
                [
                    'to' => $to,
                    'variables' => $variables
                ]
            ];
        }

        $payload = [
            'recipients' => $recipients,
            'from' => [
                'email' => $this->fromEmail
            ],
            'domain' => $this->domain,
            'template_id' => $templateId
        ];

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'authkey' => $this->authKey,
            ])->post($this->baseUrl, $payload);

            if ($response->successful()) {
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
