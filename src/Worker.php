<?php

namespace App;

use App\Client\ImapClient;
use App\Model\Account;
use Psr\Log\LoggerInterface;
use Throwable;

final class Worker
{
    private const MEMORY_LIMIT = 134_217_728; // 128MB
    private const USLEEP = 1_000_000;

    private LoggerInterface $logger;
    private ImapClient $imap;

    public function __construct(LoggerInterface $logger, ImapClient $imap)
    {
        $this->imap = $imap;
        $this->logger = $logger;
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
        $storage = new Storage();
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
                $this->task($storage->getAccount());
            } catch (Throwable $e) {
                $this->logger->error((string) $e);
            }
        }
        $this->logger->info('Worker finished');
    }

    private function task(Account $account): void
    {
        $this->logger->info('Worker task started');

        $mailbox = $this->imap->getMailbox($account);
        if ($mailbox) {
            $this->imap->forwardMailsToTelegram($mailbox, $account->telegramChatId);
        }

        $this->logger->info('Worker task finished');
    }
}
