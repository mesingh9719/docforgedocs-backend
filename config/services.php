<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'msg91' => [
        'key' => env('MSG91_AUTH_KEY'),
        'domain' => env('MSG91_DOMAIN', 'docforgedocs.com'),
        'from_email' => env('MSG91_FROM_EMAIL', 'no-reply@docforgedocs.com'),
        'verification_template_id' => env('MSG91_VERIFICATION_TEMPLATE_ID'),
        'invitation_template_id' => env('MSG91_INVITATION_TEMPLATE_ID'),
    ],
    'app' => [
        'email_environment' => env('EMAIL_ENVIRONMENT', 'local'),
    ],

];
