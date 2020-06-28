<?php

/** @noinspection PhpIllegalPsrClassPathInspection */
/** @noinspection PhpUnhandledExceptionInspection */

use M2T\App;
use M2T\Client\TelegramClient;
use Codeception\Test\Unit;
use Monolog\Logger;

class TelegramClientTest extends Unit
{
    protected BaseTester $tester;
    protected int $chatId;
    private Logger $logger;

    public function __construct()
    {
        parent::__construct();
        new App();
        $this->chatId = App::get('test')['chatId'];
        $this->logger = new Logger('test');
    }

    public function testSendMessage(): void
    {
        $client = new TelegramClient($this->logger);
        $result = $client->sendMessage($this->chatId, 'test');
        static::assertTrue($result);
    }

    public function testSendMessageLong(): void
    {
        $client = new TelegramClient($this->logger);
        $result = $client->sendMessage($this->chatId, str_repeat('Тестовое сообщение ', 250));
        static::assertTrue($result);
    }

    public function testSendDocument(): void
    {
        $client = new TelegramClient($this->logger);
        $filepath = '/app/tests/_data/111.txt';
        $result = $client->sendDocument($this->chatId, '111.txt', filesize($filepath), file_get_contents($filepath));
        static::assertTrue($result);
    }
}
