<?php

/** @noinspection PhpUnhandledExceptionInspection */

namespace Unit;

use UnitTester;
use Codeception\Test\Unit;
use M2T\App;
use M2T\Client\TelegramClient;

class TelegramClientTest extends Unit
{
    protected UnitTester $tester;
    protected int $chatId;
    protected TelegramClient $client;

    public function __construct()
    {
        parent::__construct();
        $this->chatId = getenv('TEST_CHAT_ID') ?: App::get('TEST_CHAT_ID');
        $this->client = App::get(TelegramClient::class);
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
        $result = $this->client->sendDocument(
            $this->chatId,
            '111.txt',
            filesize($filepath),
            file_get_contents($filepath)
        );
        static::assertTrue($result);
    }
}
