<?php

namespace App\Client;

use App\App;
use JsonException;
use Psr\Log\LoggerInterface;

class TelegramApiClient
{
    public const BASE_URL = 'https://api.telegram.org/bot';

    protected LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function sendMessage(int $chatId, string $text): bool
    {
        $ch = curl_init();
        curl_setopt_array(
            $ch,
            [
                CURLOPT_URL => static::BASE_URL . App::get('telegramToken') . '/sendMessage',
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => [
                    'chat_id' => $chatId,
                    'text' => $text,
                ],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 10,
            ]
        );

        $result = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            $this->logger->error('Telegram.sendMessage: ' . $error);
            return false;
        }

        try {
            $response = json_decode($result, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            $this->logger->error('Telegram.sendMessage: json decode error');
            return false;
        }

        if (!isset($response['ok'])) {
            $this->logger->error('Telegram:sendMessage: wrong response');
            return false;
        }

        if ($response['ok'] !== true) {
            $this->logger->error('Telegram:sendMessage: ' . ($response['description'] ?? 'no description'));
        }

        return true;
    }
}
