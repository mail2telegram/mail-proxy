<?php

return [
    'cryptoKey' => 'XXX',
    'logLevel' => 'debug',
    'telegramToken' => getenv('TELEGRAM_TOKEN') ?: 'XXX',
    'redis' => [
        'host' => 'm2t_redis',
    ],
    // for tests only
    'testChatId' => getenv('TEST_CHAT_ID') ?: 123456,
    'testEmailPwd' => getenv('TEST_EMAIL_PWD') ?: 'XXX',
];
