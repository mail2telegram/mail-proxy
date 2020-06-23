<?php

/** @noinspection PhpFullyQualifiedNameUsageInspection */

use App\Model\Account;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

return [
    'env' => 'prod', // prod | dev
    'telegramToken' => 'XXX',
    'test' => [
        'accounts' => [
            new Account('{imap.gmail.com:993/imap/ssl}INBOX', 'mail2telegram.app@gmail.com', 'XXX', 123456),
        ],
    ],
    LoggerInterface::class => static function () {
        $stream = new StreamHandler(STDERR);
        //$stream->setFormatter(new \Dev\CliFormatter());
        return (new Logger('app'))->pushHandler($stream);
    },
];

// Для Gmail нужно включить "less secured apps"
// https://stackoverflow.com/questions/32222250/connect-to-gmail-with-php-imap
