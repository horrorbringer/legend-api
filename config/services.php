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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
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
    'bakong' => [
        'base_url' => env('KHQR_API_URL', 'https://api-bakong.nbc.gov.kh'),
        'token' => env('KHQR_API_TOKEN'),
        'account_id' => env('KHQR_ACCOUNT_INFORMATION'),
        'merchant_name' => env('KHQR_MERCHANT_NAME', 'Cinema Booking System'),
        'merchant_city' => env('KHQR_MERCHANT_CITY', 'Phnom Penh'),
        'merchant_id' => env('KHQR_MERCHANT_ID'),
        'acquiring_bank' => env('KHQR_ACQUIRING_BANK'),
        'mobile_number' => env('KHQR_MERCHANT_PHONE', '015748353'),
    ],

];
