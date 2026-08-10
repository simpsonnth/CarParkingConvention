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
        'webhook_secret' => env('RESEND_WEBHOOK_SECRET'),
    ],

    'brevo' => [
        // Used to detect exhausted free-plan send credits (SMTP can still return 250).
        'key' => env('BREVO_API_KEY'),
    ],

    'mailersend' => [
        'key' => env('MAILERSEND_API_KEY'),
        // Stop using MailerSend when remaining daily API requests reach this floor.
        'reserve' => env('MAILERSEND_API_RESERVE', 5),
    ],

    'chrome' => [
        'binary' => env('CHROME_PATH', '/usr/bin/google-chrome-stable'),
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

];
