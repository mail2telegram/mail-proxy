<?php

namespace App\Client;

use App\App;
use CURLFile;
use JsonException;
use Psr\Log\LoggerInterface;

class TelegramClient
{
    public const BASE_URL = 'https://api.telegram.org/bot';

    protected LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function sendMessage(int $chatId, string $text): bool
    {
        $result = $this->execute(
            'sendMessage',
            [
                'chat_id' => $chatId,
                'text' => $text,
                'disable_web_page_preview' => true,
            ]
        );
        return (bool) $result;
    }

    public function sendDocument(int $chatId, string $filePath): array
    {
        $result = $this->execute(
            'sendDocument',
            [
                'chat_id' => $chatId,
                'document' => new CURLFile($filePath),
                'disable_web_page_preview' => true,
                'disable_notification' => true,
            ]
        );
        return $result['document'] ?? [];
    }

    protected function execute(string $method, array $data): array
    {
        $ch = curl_init();
        curl_setopt_array(
            $ch,
            [
                CURLOPT_URL => 'https://api.telegram.org/bot' . App::get('telegramToken') . '/' . $method,
                CURLOPT_POST => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_POSTFIELDS => $data,
            ]
        );
        $result = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            $this->logger->error('Telegram: ' . $error);
            return [];
        }

        try {
            $response = json_decode($result, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            $this->logger->error('Telegram: json decode error');
            return [];
        }

        if (!isset($response['ok'])) {
            $this->logger->error('Telegram: wrong response');
            return [];
        }

        if ($response['ok'] !== true) {
            $this->logger->error('Telegram: ' . ($response['description'] ?? 'no description'));
            return [];
        }

        return $response['result'];
    }
}
