<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Attachments disk
    |--------------------------------------------------------------------------
    |
    | Production should use "google" (Google Drive). Tests use "local".
    |
    */
    'disk' => env('LESSONS_LEARNED_DISK', 'google'),

    'max_upload_files' => 10,

    'max_upload_kilobytes' => 20480,

    'allowed_mimes' => [
        'pdf',
        'jpg',
        'jpeg',
        'png',
        'gif',
        'webp',
        'doc',
        'docx',
        'xls',
        'xlsx',
        'txt',
        'csv',
        'mp3',
        'wav',
        'webm',
        'weba',
        'ogg',
        'm4a',
        'mp4',
        'mov',
    ],
];
