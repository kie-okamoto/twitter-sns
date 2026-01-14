<?php

return [

    // 既存のサービス設定（mailgun, postmark, ses など）があればここに並びます

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    // ★ これを必ず追加
    'firebase' => [
        'credentials' => env('FIREBASE_CREDENTIALS'),
        'project_id'  => env('FIREBASE_PROJECT_ID'),
    ],

];
