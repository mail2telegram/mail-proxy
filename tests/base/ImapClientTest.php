<?php

/** @noinspection PhpIllegalPsrClassPathInspection PhpUnhandledExceptionInspection */

use M2T\App;
use M2T\Client\ImapClient;
use Codeception\Test\Unit;
use PhpImap\Mailbox;

class ImapClientTest extends Unit
{
    protected BaseTester $tester;

    public function __construct()
    {
        parent::__construct();
        new App();
    }

    public function testGetMailbox(): void
    {
        /** @var ImapClient $client */
        $client = App::get(ImapClient::class);
        $email = App::get('test')['emails'][0];

        $mailbox = $client->getMailbox($email);
        static::assertInstanceOf(Mailbox::class, $mailbox);
    }
}
