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

    'frontend_url' => env('FRONTEND_URL', 'http://localhost:5173'),

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
        'domain' => env('MSG91_DOMAIN', 'mail.docforgedocs.com'),
        'from_email' => env('MSG91_FROM_EMAIL', 'no-reply@mail.docforgedocs.com'),
        'verification_template_id' => env('MSG91_VERIFICATION_TEMPLATE_ID', 'email_verification_docforge_docs'),
        'invitation_template_id' => env('MSG91_MEMBER_INVITE_TEMPLATE_ID', 'member_invitation_for_docforgedocs'),
        'password_reset_template_id' => env('RESET_PASSWORD_TEMPLATE_ID', 'reset_password_docforgedocs'),
        'document_shared_template_id' => env('DOCUMENT_SHARED_TEMPLATE_ID', 'document_shared_template_docforgedocs'),
    ],
    'app' => [
        'email_environment' => env('EMAIL_ENVIRONMENT', 'local'),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

];
