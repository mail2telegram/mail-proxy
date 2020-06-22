<?php

/** @noinspection PhpIllegalPsrClassPathInspection */
/** @noinspection PhpUnhandledExceptionInspection */

use App\App;
use App\Client\TelegramClient;
use Codeception\Test\Unit;

class TelegramClientTest extends Unit
{
    protected BaseTester $tester;

    public function testSendMessage(): void
    {
        new App();
        $testMailBox = App::get('testMailBox');

        /** @var \App\Client\TelegramClient $client */
        $client = App::get(TelegramClient::class);
        $result = $client->sendMessage($testMailBox['chatId'], 'test');
        static::assertTrue($result);
    }

    public function testSendDocument(): void
    {
        new App();
        $testMailBox = App::get('testMailBox');

        /** @var \App\Client\TelegramClient $client */
        $client = App::get(TelegramClient::class);
        $result = $client->sendDocument($testMailBox['chatId'], '/app/tests/_data/111.txt');
        static::assertSame('111.txt', $result['file_name']);
    }
}
