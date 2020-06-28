<?php

/** @noinspection PhpIllegalPsrClassPathInspection PhpUnhandledExceptionInspection */

use M2T\App;
use M2T\Client\TelegramClient;
use Codeception\Test\Unit;
use Monolog\Logger;

class TelegramClientTest extends Unit
{
    protected BaseTester $tester;
    protected int $chatId;
    protected TelegramClient $client;

    public function __construct()
    {
        parent::__construct();
        new App();
        $this->chatId = App::get('test')['chatId'];
        $this->client = new TelegramClient(new Logger('test'));
    }

    public function testSendMessage(): void
    {
        /** @noinspection JsonEncodingApiUsageInspection */
        $markup = json_encode(
            [
                'inline_keyboard' => [
                    [
                        [
                            'text' => 'Reply',
                            'callback_data' => 'Reply',
                        ],
                    ],
                ],
            ]
        );
        $result = $this->client->sendMessage($this->chatId, 'test', $markup);
        static::assertTrue($result);
    }

    public function testSendMessageLong(): void
    {
        /** @noinspection JsonEncodingApiUsageInspection */
        $markup = json_encode(
            [
                'inline_keyboard' => [
                    [
                        [
                            'text' => 'Reply',
                            'callback_data' => 'Reply',
                        ],
                    ],
                ],
            ]
        );
        $result = $this->client->sendMessage($this->chatId, str_repeat('Тестовое сообщение ', 250), $markup);
        static::assertTrue($result);
    }

    public function testSendDocument(): void
    {
        $filepath = '/app/tests/_data/111.txt';
        $result = $this->client->sendDocument($this->chatId, '111.txt', filesize($filepath), file_get_contents($filepath));
        static::assertTrue($result);
    }
}
