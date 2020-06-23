<?php

namespace App\Client;

use App\Model\Account;
use PhpImap\Exceptions\InvalidParameterException;
use PhpImap\Mailbox;
use Psr\Log\LoggerInterface;

class ImapClient
{
    protected LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function getMailbox(Account $account): ?Mailbox
    {
        try {
            return new Mailbox($account->imapPath, $account->imapLogin, $account->imapPassword);
        } catch (InvalidParameterException $e) {
            $this->logger->error((string) $e);
        }
        return null;
    }

    /**
     * @param \PhpImap\Mailbox $mailbox
     * @return int[]
     */
    public function getMails(Mailbox $mailbox): array
    {
        return $mailbox->searchMailbox('UNSEEN');
    }
}
