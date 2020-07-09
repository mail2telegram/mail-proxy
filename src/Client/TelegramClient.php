<?php

namespace M2T\Client;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use M2T\App;
use PhpImap\IncomingMail;
use Psr\Log\LoggerInterface;
use Throwable;

class TelegramClient
{
    protected const BASE_URL = 'https://api.telegram.org/bot';
    protected const MAX_TEXT_LENGTH = 4096;
    protected const MAX_FILE_SIZE = 10_485_760; // 10 MB

    protected LoggerInterface $logger;
    protected ClientInterface $client;

    public function __construct(LoggerInterface $logger, ?ClientInterface $client = null)
    {
        $this->logger = $logger;
        $this->client = $client
            ?? new Client(
                [
                    'base_uri' => static::BASE_URL . App::get('telegramToken') . '/',
                    'timeout' => App::get('telegramTimeout'),
                ]
            );
    }

    public function sendMessage(int $chatId, string $text, string $replyMarkup = ''): bool
    {
        if (mb_strlen($text) >= static::MAX_TEXT_LENGTH) {
            $chanks = static::explodeText($text);
            $length = count($chanks);
            foreach ($chanks as $index => $textChank) {
                $result = $this->sendMessage($chatId, $textChank, $index === $length - 1 ? $replyMarkup : '');
                if (!$result) {
                    return false;
                }
            }
            return true;
        }
        $data = [
            'form_params' => [
                'chat_id' => $chatId,
                'text' => $text,
                'disable_web_page_preview' => true,
            ],
        ];
        if ($replyMarkup) {
            $data['form_params']['reply_markup'] = $replyMarkup;
        }
        $result = $this->execute('sendMessage', $data);
        return (bool) $result;
    }

    /**
     * @param int    $chatId
     * @param string $filename
     * @param int    $size size int bytes
     * @param string $contents
     * @return bool
     */
    public function sendDocument(int $chatId, string $filename, int $size, string $contents): bool
    {
        if ($size >= static::MAX_FILE_SIZE) {
            $msg = "File '{$filename}' too big";
            return $this->sendMessage($chatId, $msg);
        }
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
        return isset($result['document']);
    }

    protected function execute(string $method, array $data): array
    {
        try {
            $response = $this->client->request('POST', $method, $data);
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

    public function formatMail(IncomingMail $mail, string $to): string
    {
        $from = $mail->fromName
            ? "{$mail->fromName} <{$mail->fromAddress}>"
            : "<{$mail->fromAddress}>";

        // Если email не совпадает с To из письма (пересылка, группа рассылки, etc) - добавим OriginTo
        $originTo = '';
        if ($mail->toString && false === strpos($mail->toString, $to)) {
            $originTo = $mail->toString;
        }

        return $mail->subject
            . "\n\nDate: {$mail->date}"
            . "\nFrom: $from"
            . "\nTo: <{$to}>"
            . ($originTo ? "\nOriginTo: {$originTo}" : '')
            . "\n\n{$mail->textPlain}";
    }
}
