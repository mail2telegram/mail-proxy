<?php

/**
 * @noinspection PhpFullyQualifiedNameUsageInspection
 * @noinspection PhpUnreachableStatementInspection
 */

return [
    'env' => 'prod', // prod | dev
    'attachmentsDir' => '/app/tmp/attachments',
    'telegramToken' => 'XXX',
    'testMailBox' => [
//        'imapPath' => '{imap.gmail.com:993/imap/ssl/novalidate-cert}INBOX',
        'imapPath' => '{imap.gmail.com:993/imap/ssl}INBOX',
        'login' => 'mail2telegram.app@gmail.com',
        'pwd' => 'XXXX',
        'chatId' => 123456,
    ]
];
