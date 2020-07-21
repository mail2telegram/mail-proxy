<?php

namespace M2T\Client;

use M2T\Model\Mailbox as MailboxAccount;
use PhpImap\Mailbox;
use Psr\Log\LoggerInterface;
use Throwable;

class ImapClient
{
    protected LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function getMailbox(MailboxAccount $account): ?Mailbox
    {
        try {
            return new Mailbox(
                "{{$account->imapHost}:{$account->imapPort}/imap/{$account->imapSocketType}}INBOX",
                $account->email,
                $account->getPwd()
            );
        } catch (Throwable $e) {
            $this->logger->error((string) $e);
        }
        return null;
    }

    /**
     * @param Mailbox $mailbox
     * @return int[]
     */
    public function getMails(Mailbox $mailbox): array
    {
        return $mailbox->searchMailbox('SINCE ' . date('d-M-Y', strtotime('-1 days')));
    }
}
