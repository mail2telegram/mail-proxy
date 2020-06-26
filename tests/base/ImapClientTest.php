<?php

/** @noinspection PhpIllegalPsrClassPathInspection */
/** @noinspection PhpUnhandledExceptionInspection */

use App\App;
use App\Client\ImapClient;
use App\Storage;
use Codeception\Test\Unit;
use PhpImap\Mailbox;

class ImapClientTest extends Unit
{
    protected BaseTester $tester;

    public function testGetMailbox(): void
    {
        new App();
        $account = (new Storage())->getAccount();
        $email = $account->emails[0];

        /** @var ImapClient $client */
        $client = App::get(ImapClient::class);
        $mailbox = $client->getMailbox($email);
        static::assertInstanceOf(Mailbox::class, $mailbox);
    }
}
