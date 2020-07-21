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

    public function providerMail(): array
    {
        /** @noinspection PhpIncludeInspection */
        return require codecept_data_dir('/mailAccountList.php');
    }

    /**
     * @dataProvider providerMail
     * @param $mailAccount
     * @param $expected
     */
    public function testGetMails(\M2T\Model\Mailbox $mailAccount, bool $expected): void
    {
        $client = new ImapClient(App::get(LoggerInterface::class));
        $mailbox = $client->getMailbox($mailAccount);
        static::assertInstanceOf(Mailbox::class, $mailbox);
        $result = $client->getMails($mailbox);
        static::assertIsArray($result);
    }
}
