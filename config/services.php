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

    'insw' => [
        'auth_url' => env('INSW_AUTH_URL'),
        'token_url' => env('INSW_TOKEN_URL'),
        'base_url' => env('INSW_API_BASE_URL'),
        'client_id' => env('INSW_CLIENT_ID'),
        'client_secret' => env('INSW_CLIENT_SECRET'),
        'provision_key' => env('INSW_PROVISION_KEY'),
        'user_id' => env('INSW_USER_ID'),
        'redirect_uri' => env('INSW_REDIRECT_URI'),
    ],

    'document_verification' => [
        // Bahasa tesseract, gabungan pakai '+' (mis. 'ind+eng'). Traineddata bahasa selain yang
        // ikut terpasang bersama binary tesseract (biasanya cuma 'eng') wajib diinstal manual di
        // server - lihat claude/otomatis-verifikasi.md bagian Hasil implementasi.
        'ocr_lang' => env('DOCUMENT_VERIFICATION_OCR_LANG', 'eng'),
        'oss_signer_keywords' => ['LEMBAGA OSS', 'ONLINE SINGLE SUBMISSION', 'OSS RBA', 'OSS-RBA'],
    ],

    'kapso' => [
        // Env var name is lowercase ("kapso_api_key") per the integration spec this project
        // was given - kept as-is rather than renamed to the usual UPPER_SNAKE convention.
        'api_key' => env('kapso_api_key'),
        'base_url' => env('KAPSO_API_BASE_URL', 'https://api.kapso.ai'),
        'phone_number_id' => env('KAPSO_PHONE_NUMBER_ID'),
    ],

];
