<?php

/** @noinspection PhpUnhandledExceptionInspection */

namespace Base;

use BaseTester;
use Codeception\Test\Unit;
use M2T\App;
use M2T\Client\ImapClient;
use PhpImap\Mailbox;

class ImapClientBaseTest extends Unit
{
    protected BaseTester $tester;

    public function testGetMails(): void
    {
        /** @var ImapClient $client */
        $client = App::get(ImapClient::class);

        foreach ($this->tester->accountProvider()->emails as $email) {
            $mailbox = $client->getMailbox($email);
            static::assertInstanceOf(Mailbox::class, $mailbox);

            $result = $client->getMails($mailbox);
            static::assertIsArray($result);
        }
    }
}
