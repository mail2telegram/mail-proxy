<?php

use App\Model\Account;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use pahanini\Monolog\Formatter\CliFormatter;
use Psr\Log\LoggerInterface;

return [
    'workerMemoryLimit' => 134_217_728, // 128MB
    'workerInterval' => 10_000, // micro seconds
    'telegramToken' => 'XXX',
    'test' => [
        'accounts' => [
            new Account(
                'mail2telegram.app@gmail.com',
                'XXX',
                123456,
                'imap.gmail.com',
                993,
                'ssl',
                'smtp.gmail.com',
                465,
                'ssl'
            ),
        ],
    ],
    LoggerInterface::class => static function () {
        $stream = new StreamHandler(STDERR);
        $stream->setFormatter(new CliFormatter());
        return (new Logger('app'))->pushHandler($stream);
    },
];

// Для Gmail нужно включить "less secured apps"
// https://stackoverflow.com/questions/32222250/connect-to-gmail-with-php-imap
