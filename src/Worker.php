<?php

namespace App;

use App\Client\ImapClient;
use App\Client\TelegramClient;
use Psr\Log\LoggerInterface;
use Throwable;

final class Worker
{
    private const MEMORY_LIMIT = 134_217_728; // 128MB
    private const USLEEP = 1_000_000;

    private LoggerInterface $logger;
    private StorageInterface $storage;
    private ImapClient $imap;
    private TelegramClient $telegram;

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
            if (memory_get_usage(true) >= self::MEMORY_LIMIT) {
                $this->logger->warning('Worker out of memory');
                break;
            }
            usleep(self::USLEEP);
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
            $this->telegram->sendMessage($account->telegramChatId, $this->telegram->formatMail($mail));
            if (App::get('env') !== 'prod') {
                $this->logger->debug('Message: ' . $this->telegram->formatMail($mail));
            }
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
}
