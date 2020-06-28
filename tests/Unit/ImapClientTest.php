<?php

/** @noinspection PhpUnhandledExceptionInspection */

namespace Unit;

use UnitTester;
use Codeception\Test\Unit;
use M2T\App;
use M2T\Client\ImapClient;
use PhpImap\Mailbox;

class ImapClientTest extends Unit
{
    protected UnitTester $tester;

    public function testGetMails(): void
    {
        /** @var ImapClient $client */
        $client = App::get(ImapClient::class);
        $email = $this->tester->accountProvider()->emails[0];

        $mailbox = $client->getMailbox($email);
        static::assertInstanceOf(Mailbox::class, $mailbox);

        $result = $client->getMails($mailbox);
        static::assertIsArray($result);
    }
}
