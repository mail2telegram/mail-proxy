<?php

use App\Storage;
use App\StorageInterface;
use M2T\App;
use M2T\Model\Account;
use M2T\Model\Email;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use pahanini\Monolog\Formatter\CliFormatter;
use Psr\Log\LoggerInterface;

return [
    'workerMemoryLimit' => 134_217_728, // 128MB
    'workerInterval' => 10_000, // micro seconds
    'telegramToken' => 'XXX',
    'redis' => [
        'host' => 'm2t_redis',
    ],
    'test' => [
        'accounts' => [
            new Account(
                123456,
                [
                    new Email(
                        'mail2telegram.app@gmail.com',
                        'XXX',
                        'imap.gmail.com',
                        993,
                        'ssl',
                        'smtp.gmail.com',
                        465,
                        'ssl'
                    ),
                ]
            ),
        ],
    ],
    'shared' => [
        LoggerInterface::class,
    ],
    LoggerInterface::class => static function () {
        $stream = new StreamHandler(STDERR);
        $stream->setFormatter(new CliFormatter());
        return (new Logger('app'))->pushHandler($stream);
    },
    StorageInterface::class => static function () {
        return new Storage();
    },
    Redis::class => static function () {
        static $connect;
        if (null === $connect) {
            $connect = new Redis();
        }
        if (!$connect->isConnected() && !$connect->pconnect(App::get('redis')['host'])) {
            throw new RuntimeException('No Redis connection');
        }
        return $connect;
    },
];

// Для Gmail нужно включить "less secured apps"
// https://stackoverflow.com/questions/32222250/connect-to-gmail-with-php-imap
