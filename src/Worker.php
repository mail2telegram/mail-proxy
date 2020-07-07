<?php

namespace M2T;

use M2T\Client\ImapClient;
use M2T\Client\TelegramClient;
use M2T\Model\Account;
use M2T\Model\Email;
use Psr\Log\LoggerInterface;
use Redis;
use RedisException;
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
                if (is_a($e, RedisException::class)) {
                    $this->reconnectRedis();
                }
            }
        }
        $this->logger->info('Worker finished');
    }

    /** @SuppressWarnings(PHPMD.EmptyCatchBlock) */
    private function reconnectRedis(): void
    {
        $config = App::get('redis');
        usleep(App::get('workerReconnectInterval'));
        try {
            /** @phan-suppress-next-line PhanParamTooManyInternal */
            $this->redis->pconnect(
                $config['host'],
                $config['port'] ?? 6379,
                $config['timeout'] ?? 0.0,
                $config['persistentId'] ?? null,
                $config['retryInterval'] ?? 0,
                $config['readTimeout'] ?? 0.0
            );
        } catch (Throwable $e) {
        }
    }

    private function task(): void
    {
        $this->logger->debug('Worker task started');
        $account = $this->accounter->get();
        if (!$account) {
            $this->logger->debug('Worker no tasks');
            return;
        }

        $key = 'mailProxyLock:' . $account->chatId;
        if (!$this->redis->setNx($key, true)) {
            $this->logger->debug('Worker task locked');
            return;
        }
        $this->redis->expire($key, $this->lockTTL);

        foreach ($account->emails as $email) {
            $this->processMailbox($account, $email);
        }

        $this->redis->del($key);
        $this->logger->debug('Worker task finished');
    }

    private function processMailbox(Account $account, Email $email): void
    {
        $mailbox = $this->imap->getMailbox($email);
        if (!$mailbox) {
            return;
        }

        $mailsIds = $this->imap->getMails($mailbox);
        if (!$mailsIds) {
            return;
        }

        $key = 'lastMailId:' . $account->chatId . ':' . $mailbox->getLogin();
        $lastId = $this->redis->get($key) ?: 0;
        $mailsIds = array_filter($mailsIds, fn($id) => $id > $lastId);
        if (!$mailsIds) {
            return;
        }

        // т.к. мы запрашиваем почту с предыдущего дня, то больше 2 дней хранить не нужно
        $this->redis->setex($key, 172_800, max($mailsIds));
        foreach ($mailsIds as $id) {
            $mail = $mailbox->getMail($id, false);
            $this->telegram->sendMessage(
                $account->chatId,
                $this->telegram->formatMail($mail),
                $this->getReplyMarkup($id)
            );
            $this->logger->debug('MailId: ' . $id);
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

    // @todo draft
    private function getReplyMarkup(int $mailId): string
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
                            'text' => 'Delete',
                            'callback_data' => 'delete:' . $mailId,
                        ],
                    ],
                ],
            ]
        );
    }
}
