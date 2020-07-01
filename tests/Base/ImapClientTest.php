<?php

/** @noinspection PhpUnhandledExceptionInspection */

namespace Base;

use BaseTester;
use Codeception\Test\Unit;
use M2T\App;
use M2T\Client\ImapClient;
use PhpImap\Mailbox;
use Psr\Log\LoggerInterface;

class ImapClientTest extends Unit
{
    protected BaseTester $tester;

    public function testGetMails(): void
    {
        $client = new ImapClient(App::get(LoggerInterface::class));
        foreach ($this->tester->emailProvider() as $email) {
            $mailbox = $client->getMailbox($email);
            static::assertInstanceOf(Mailbox::class, $mailbox);
            $result = $client->getMails($mailbox);
            static::assertIsArray($result);
        }
    }
}
