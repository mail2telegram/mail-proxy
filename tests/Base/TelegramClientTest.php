<?php

/** @noinspection PhpUnhandledExceptionInspection */

namespace Base;

use BaseTester;
use Codeception\Test\Unit;
use M2T\App;
use M2T\Client\TelegramClient;
use Psr\Log\LoggerInterface;

class TelegramClientTest extends Unit
{
    protected BaseTester $tester;
    protected int $chatId;
    protected TelegramClient $client;

    public function __construct()
    {
        parent::__construct();
        $this->client = new TelegramClient(App::get(LoggerInterface::class));
        $this->chatId = getenv('TEST_CHAT_ID') ?: App::get('testChatId');
    }

    protected static function getMarkup()
    {
        /** @noinspection JsonEncodingApiUsageInspection */
        return json_encode(
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
    }

    public function testSendMessage(): void
    {
        $result = $this->client->sendMessage($this->chatId, 'test', static::getMarkup());
        static::assertTrue($result);
    }

    public function testSendMessageLong(): void
    {
        $result = $this->client->sendMessage(
            $this->chatId,
            str_repeat('Тестовое сообщение ', 250),
            static::getMarkup()
        );
        static::assertTrue($result);
    }

    public function testSendDocument(): void
    {
        $filepath = '/app/tests/_data/111.txt';
        $result = $this->client->sendDocument(
            $this->chatId,
            '111.txt',
            filesize($filepath),
            file_get_contents($filepath)
        );
        static::assertTrue($result);
    }
}
