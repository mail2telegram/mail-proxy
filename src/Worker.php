<?php

namespace M2T;

use M2T\Client\ImapClient;
use M2T\Client\TelegramClient;
use Psr\Log\LoggerInterface;
use Redis;
use Throwable;

final class Worker
{
    private LoggerInterface $logger;
    private Redis $redis;
    private AccountIterator $accounter;
    private ImapClient $imap;
    private TelegramClient $telegram;
    private int $memoryLimit;
    private int $interval;
    private int $lockTTL;

    public function __construct(
        LoggerInterface $logger,
        Redis $redis,
        AccountIterator $accounter,
        ImapClient $imap,
        TelegramClient $telegram
    ) {
        $this->logger = $logger;
        $this->redis = $redis;
        $this->accounter = $accounter;
        $this->imap = $imap;
        $this->telegram = $telegram;
        $this->memoryLimit = App::get('workerMemoryLimit');
        $this->interval = App::get('workerInterval');
        $this->lockTTL = App::get('workerLockTTL');

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
                $this->task();
            } catch (Throwable $e) {
                $this->logger->error((string) $e);
            }
        }
        $this->logger->info('Worker finished');
    }

    private function task(): void
    {
        $this->logger->debug('Worker task started');
        $account = $this->accounter->get();
        if (!$account) {
            $this->logger->debug('Worker no tasks');
            return;
        }

        $key = 'lock:imap:' . $account->chatId;
        if (!$this->redis->setNx($key, true)) {
            $this->logger->debug('Worker task locked');
            return;
        }
        $this->redis->expire($key, $this->lockTTL);

        foreach ($account->emails as $email) {
            $mailbox = $this->imap->getMailbox($email);
            if (!$mailbox) {
                continue;
            }
            $mailsIds = $this->imap->getMails($mailbox);
            foreach ($mailsIds as $id) {
                $mail = $mailbox->getMail($id, false);
                $this->telegram->sendMessage(
                    $account->chatId,
                    $this->telegram->formatMail($mail),
                    $this->getReplyMarkup()
                );
                $this->logger->debug('Message: ' . $this->telegram->formatMail($mail));
                if ($mail->hasAttachments()) {
                    $attachments = $mail->getAttachments();
                    foreach ($attachments as $attach) {
                        $this->telegram->sendDocument(
                            $account->chatId,
                            $attach->name,
                            $attach->sizeInBytes,
                            $attach->getContents()
                        );
                    }
                }
            }
        }

        $this->redis->del($key);
        $this->logger->debug('Worker task finished');
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
