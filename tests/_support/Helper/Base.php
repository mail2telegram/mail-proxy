<?php

namespace Helper;

use Codeception\Module;
use M2T\App;
use M2T\Model\Mailbox;

class Base extends Module
{
    public function _initialize(): void
    {
        /** @noinspection PhpIncludeInspection */
        require_once codecept_root_dir() . '/vendor/autoload.php';
        new App();
    }

    /**
     * @return Mailbox[]
     */
    public function emailProvider(): array
    {
        $pwd = (require './config.php')['testEmailPwd'];
        return [
            new Mailbox(
                'mail2telegram.app@gmail.com',
                $pwd,
                'imap.gmail.com',
                993,
                'ssl',
                'smtp.gmail.com',
                465,
                'ssl'
            ),
            new Mailbox(
                'mail2telegram.app@yandex.ru',
                $pwd,
                'imap.yandex.com',
                993,
                'ssl',
                'smtp.yandex.com',
                465,
                'ssl'
            ),
            new Mailbox(
                'mail2telegram.app@mail.ru',
                $pwd,
                'imap.mail.ru',
                993,
                'ssl',
                'smtp.mail.ru',
                465,
                'ssl'
            ),
        ];
    }
}
