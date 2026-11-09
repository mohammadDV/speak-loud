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

    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'notif_chat_id' => env('TELEGRAM_NOTIF_CHAT_ID'),
        'error_chat_id' => env('TELEGRAM_ERROR_CHAT_ID'),
        'queue' => env('TELEGRAM_QUEUE', 'low'),
        'enabled' => env('TELEGRAM_NOTIFICATIONS_ENABLED', true),
    ],

    'paypal' => [
        'donate_email' => env('PAYPAL_DONATE_EMAIL', 'mohammad.daneshmandvojdani@gmail.com'),
        'currency' => env('PAYPAL_DONATE_CURRENCY', 'USD'),
        'item_name' => env('PAYPAL_DONATE_ITEM_NAME', 'SpeakLoudMate donation'),
    ],

    'google_tag_manager' => [
        'id' => env('GOOGLE_TAG_MANAGER_ID'),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI', '/auth/google/callback'),
    ],

    'home' => [
        'demo_video_url' => env('HOME_DEMO_VIDEO_URL'),
    ],

];
