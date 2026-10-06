<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Incoming Mail Server (IMAP / POP3) Settings
    |--------------------------------------------------------------------------
    | Configure your email server details to automatically fetch emails
    | and convert them into support tickets.
    */
    'incoming' => [
        'enabled'    => true,
        'protocol'   => 'imap', // 'imap' or 'pop3'
        'host'       => 'imap.gmail.com',
        'port'       => 993,
        'encryption' => 'ssl', // 'ssl', 'tls', or null
        'username'   => 'your-email@gmail.com',
        'password'   => 'your-app-password',
        'validate_cert' => false,
        'delete_after_import' => false, // Set true to remove imported emails from inbox
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage Directory for Attachments
    |--------------------------------------------------------------------------
    */
    'storage' => [
        'attachment_dir' => __DIR__ . '/../public/uploads/attachments',
        'max_file_size' => 10 * 1024 * 1024, // 10 MB
        'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'txt', 'zip', 'csv', 'xlsx'],
    ]
];
