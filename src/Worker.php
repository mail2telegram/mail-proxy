<?php

namespace App;

use PhpImap\Mailbox;
use Psr\Log\LoggerInterface;
use Throwable;

final class Worker
{
    private const MEMORY_LIMIT = 134_217_728; // 128MB
    private const USLEEP = 2_000_000;

    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
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
                $this->task();
            } catch (Throwable $e) {
                $this->logger->error((string) $e);
            }
        }
        $this->logger->info('Worker finished');
    }

    /**
     * @throws \PhpImap\Exceptions\InvalidParameterException
     */
    private function task(): void
    {
        $this->logger->info('Worker task started');

        // @todo draft
        $testMailBox = App::get('testMailBox');
        $mailbox = new Mailbox($testMailBox['imapPath'], $testMailBox['login'], $testMailBox['pwd']);
        $mailsIds = $mailbox->searchMailbox('UNSEEN');
        foreach ($mailsIds as $id) {
            $mail = $mailbox->getMail($id, false);
            $debug = [
                'id' => $mail->id,
                'date' => $mail->date,
                'fromName' => $mail->fromName,
                'fromAddress' => $mail->fromAddress,
                'subject' => $mail->subject,
                'hasAttachments' => (int) $mail->hasAttachments(),
                'textPlain' => $mail->textPlain,
            ];
            $this->logger->debug(print_r($debug, true));
        }

        $this->logger->info('Worker task finished');
    }
}
