<?php

/** @noinspection PhpIllegalPsrClassPathInspection */
/** @noinspection PhpUnhandledExceptionInspection */

use App\App;
use App\Client\TelegramClient;
use App\Storage;
use Codeception\Test\Unit;

class TelegramClientTest extends Unit
{
    protected BaseTester $tester;

    public function testSendMessage(): void
    {
        new App();
        $account = (new Storage())->getAccount();

        /** @var \App\Client\TelegramClient $client */
        $client = App::get(TelegramClient::class);
        $result = $client->sendMessage($account->chatId, 'test');
        static::assertTrue($result);
    }

    public function testSendMessageLong(): void
    {
        new App();
        $account = (new Storage())->getAccount();

        /** @var \App\Client\TelegramClient $client */
        $client = App::get(TelegramClient::class);
        $result = $client->sendMessage($account->chatId, str_repeat('Тестовое сообщение ', 250));
        static::assertTrue($result);
    }

    public function testSendDocument(): void
    {
        new App();
        $account = (new Storage())->getAccount();

        /** @var \App\Client\TelegramClient $client */
        $client = App::get(TelegramClient::class);
        $filepath = '/app/tests/_data/111.txt';
        $result = $client->sendDocument($account->chatId, '111.txt', filesize($filepath), file_get_contents($filepath));
        static::assertTrue($result);
    }
}
