<?php
// filepath: /var/www/html/timeteccrm/config/imap.php

return [
    'default' => env('IMAP_DEFAULT_ACCOUNT', 'hrdf'),

    'accounts' => [
        'hrdf' => [
            'host' => env('IMAP_HOST', 'imap.gmail.com'),
            'port' => env('IMAP_PORT', 993),
            'protocol' => env('IMAP_PROTOCOL', 'imap'),
            'encryption' => env('IMAP_ENCRYPTION', 'ssl'),
            'validate_cert' => env('IMAP_VALIDATE_CERT', true),
            'username' => env('IMAP_USERNAME'),
            'password' => env('IMAP_PASSWORD'),
            'authentication' => env('IMAP_AUTHENTICATION', null),
            'proxy' => [
                'socket' => null,
                'request_fulluri' => false,
                'username' => null,
                'password' => null,
            ],
            'timeout' => 30,
            'extensions' => [],
        ],
    ],

    'options' => [
        'delimiter' => env('IMAP_OPTIONS_DELIMITER', '/'),
        'fetch' => 1,
        'sequence' => 1,
        'fetch_body' => true,
        'fetch_flags' => true,
        'soft_fail' => false,
        'debug' => env('IMAP_DEBUG', false),
    ],
];
