<?php

/** @noinspection PhpIllegalPsrClassPathInspection */
/** @noinspection PhpUnhandledExceptionInspection */

use M2T\App;
use M2T\Client\ImapClient;
use Codeception\Test\Unit;
use Monolog\Logger;
use PhpImap\Mailbox;

class ImapClientTest extends Unit
{
    protected BaseTester $tester;
    private Logger $logger;

    public function __construct()
    {
        parent::__construct();
        new App();
        $this->logger = new Logger('test');
    }

    public function testGetMailbox(): void
    {
        $email = App::get('test')['emails'][0];
        $client = new ImapClient($this->logger);
        $mailbox = $client->getMailbox($email);
        static::assertInstanceOf(Mailbox::class, $mailbox);
    }
}
