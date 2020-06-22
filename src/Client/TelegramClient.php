<?php

namespace App\Client;

use App\App;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use Throwable;

class TelegramClient
{
    public const BASE_URL = 'https://api.telegram.org/bot';
    public const MAX_TEXT_LENGTH = 4096;

    protected LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function sendMessage(int $chatId, string $text): bool
    {
        if (mb_strlen($text) >= static::MAX_TEXT_LENGTH) {
            $chanks = static::explodeText($text);
            foreach ($chanks as $t) {
                $result = $this->sendMessage($chatId, $t);
                if (!$result) {
                    return false;
                }
            }
            return true;
        }
        /** @noinspection JsonEncodingApiUsageInspection */
        $data = [
            'form_params' => [
                'chat_id' => $chatId,
                'text' => $text,
                'disable_web_page_preview' => true,
                'reply_markup' => json_encode(
                    [
                        'inline_keyboard' => [
                            [
                                [
                                    'text' => 'Reply',
                                    'callback_data' => 'Reply',
                                ],
                                [
                                    'text' => 'Archive',
                                    'callback_data' => 'Archive',
                                ],
                            ],
                        ],
                    ]
                ),
            ],
        ];
        $result = $this->execute('sendMessage', $data);
        return (bool) $result;
    }

    public function sendDocument(int $chatId, string $filename, string $contents): array
    {
        $data = [
            'multipart' => [
                [
                    'name' => 'chat_id',
                    'contents' => $chatId,
                ],
                [
                    'name' => 'disable_web_page_preview',
                    'contents' => true,
                ],
                [
                    'name' => 'disable_notification',
                    'contents' => true,
                ],
                [
                    'name' => 'document',
                    'filename' => $filename,
                    'contents' => $contents,
                ],
            ],
        ];
        $result = $this->execute('sendDocument', $data);
        return $result['document'] ?? [];
    }

    protected function execute(string $method, array $data): array
    {
        $client = new Client(
            [
                'base_uri' => static::BASE_URL . App::get('telegramToken') . '/',
                'timeout' => 10.0,
            ]
        );

        try {
            $response = $client->request('POST', $method, $data);
        } catch (Throwable $e) {
            $this->logger->error('Telegram: ' . $e);
            return [];
        }

        try {
            $response = json_decode($response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $e) {
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

    /**
     * @param string $string
     * @return array
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public static function explodeText(string $string): array
    {
        $break = '<-B-R-E-A-K->';
        $width = static::MAX_TEXT_LENGTH;

        if (strlen($string) === mb_strlen($string)) {
            return explode($break, wordwrap($string, $width, $break));
        }

        $stringWidth = mb_strlen($string);
        $breakWidth = mb_strlen($break);

        $result = '';
        $lastStart = $lastSpace = 0;

        for ($current = 0; $current < $stringWidth; $current++) {
            $char = mb_substr($string, $current, 1);

            $possibleBreak = $char;
            if ($breakWidth !== 1) {
                $possibleBreak = mb_substr($string, $current, $breakWidth);
            }

            if ($possibleBreak === $break) {
                $result .= mb_substr($string, $lastStart, $current - $lastStart + $breakWidth);
                $current += $breakWidth - 1;
                $lastStart = $lastSpace = $current + 1;
                continue;
            }

            if ($char === ' ') {
                if ($current - $lastStart >= $width) {
                    $result .= mb_substr($string, $lastStart, $current - $lastStart) . $break;
                    $lastStart = $current + 1;
                }
                $lastSpace = $current;
                continue;
            }

            if ($current - $lastStart >= $width && $lastStart < $lastSpace) {
                $result .= mb_substr($string, $lastStart, $lastSpace - $lastStart) . $break;
                $lastStart = ++$lastSpace;
                continue;
            }
        }

        if ($lastStart !== $current) {
            $result .= mb_substr($string, $lastStart, $current - $lastStart);
        }

        return explode($break, $result);
    }
}
