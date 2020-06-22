<?php

use App\Model\Account;

return [
    'env' => 'prod', // prod | dev
    'telegramToken' => 'XXX',
    'test' => [
        'accounts' => [
            new Account('{imap.gmail.com:993/imap/ssl}INBOX', 'mail2telegram.app@gmail.com', 'XXX', 123456),
        ],
    ],
];

// Для Gmail нужно включить "less secured apps"
// https://stackoverflow.com/questions/32222250/connect-to-gmail-with-php-imap
