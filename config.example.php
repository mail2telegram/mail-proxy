<?php

use M2T\App;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use pahanini\Monolog\Formatter\CliFormatter;
use Psr\Log\LoggerInterface;

return [
    'workerMemoryLimit' => 134_217_728, // 128MB
    'workerInterval' => 1_000_000, // micro seconds
    'telegramToken' => 'XXX',
    'testChatId' => 123456,
    'testEmailPwd' => 'XXX',
    'redis' => [
        'host' => 'm2t_redis',
    ],
    'shared' => [
        LoggerInterface::class,
    ],
    LoggerInterface::class => static function () {
        $stream = new StreamHandler(STDERR);
        $stream->setFormatter(new CliFormatter());
        return (new Logger('app'))->pushHandler($stream);
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
