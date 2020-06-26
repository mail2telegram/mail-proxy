<?php

namespace App;

use App\Client\ImapClient;
use App\Client\TelegramClient;
use Psr\Log\LoggerInterface;
use Throwable;

final class Worker
{
    private LoggerInterface $logger;
    private StorageInterface $storage;
    private ImapClient $imap;
    private TelegramClient $telegram;
    private int $memoryLimit;
    private int $interval;

    public function __construct(
        LoggerInterface $logger,
        StorageInterface $storage,
        ImapClient $imap,
        TelegramClient $telegram
    ) {
        $this->logger = $logger;
        $this->storage = $storage;
        $this->imap = $imap;
        $this->telegram = $telegram;
        $this->interval = App::get('workerInterval');
        $this->memoryLimit = App::get('workerMemoryLimit');

        $this->logger->info('Worker started');
        pcntl_signal(SIGTERM, [$this, 'signalHandler']);
        pcntl_signal(SIGINT, [$this, 'signalHandler']);
    }

    public function signalHandler($signo): void
    {
        switch ($signo) {
            case SIGTERM:
            case SIGINT:
                if (!defined('TERMINATED')) {
                    define('TERMINATED', true);
                    $this->logger->info('Worker terminated signal');
                }
        }
    }

    public function loop(): void
    {
        while (true) {
            if (defined('TERMINATED')) {
                break;
            }
            if (memory_get_usage(true) >= $this->memoryLimit) {
                $this->logger->warning('Worker out of memory');
                break;
            }
            usleep($this->interval);
            try {
                $this->logger->info('Worker task started');
                $this->task();
                $this->logger->info('Worker task finished');
            } catch (Throwable $e) {
                $this->logger->error((string) $e);
            }
        }
        $this->logger->info('Worker finished');
    }

    private function task(): void
    {
        $account = $this->storage->getAccount();
        $mailbox = $this->imap->getMailbox($account);
        if (!$mailbox) {
            return;
        }
        $mailsIds = $this->imap->getMails($mailbox);
        foreach ($mailsIds as $id) {
            $mail = $mailbox->getMail($id);
            $this->telegram->sendMessage(
                $account->telegramChatId,
                $this->telegram->formatMail($mail),
                $this->getReplyMarkup()
            );
            $this->logger->debug('Message: ' . $this->telegram->formatMail($mail));
            if ($mail->hasAttachments()) {
                $attachments = $mail->getAttachments();
                foreach ($attachments as $attach) {
                    $this->telegram->sendDocument(
                        $account->telegramChatId,
                        $attach->name,
                        $attach->sizeInBytes,
                        $attach->getContents()
                    );
                }
            }
        }
    }

    // @todo dratf
    private function getReplyMarkup(): string
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
                        [
                            'text' => 'Archive',
                            'callback_data' => 'Archive',
                        ],
                    ],
                ],
            ]
        );
    }
}
