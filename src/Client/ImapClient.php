<?php

namespace M2T\Client;

use M2T\Model\Email;
use PhpImap\Exceptions\InvalidParameterException;
use PhpImap\Mailbox;
use Psr\Log\LoggerInterface;
use Redis;

class ImapClient
{
    protected LoggerInterface $logger;
    protected Redis $redis;

    public function __construct(LoggerInterface $logger, Redis $redis)
    {
        $this->logger = $logger;
        $this->redis = $redis;
    }

    public function getMailbox(Email $account): ?Mailbox
    {
        try {
            return new Mailbox(
                "{{$account->imapHost}:{$account->imapPort}/imap/{$account->imapSocketType}}INBOX",
                $account->email,
                $account->pwd
            );
        } catch (InvalidParameterException $e) {
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
        $key = base64_encode($mailbox->getLogin());
        $lastId = $this->redis->get('mailLastId:' . $key) ?: 0;
        $ids = $mailbox->searchMailbox('SINCE ' .  date('d-M-Y', strtotime('-1 days')));
        $ids = array_filter($ids, fn($id) => $id > $lastId);
        if ($ids) {
            $this->redis->set('mailLastId:' . $key, max($ids));
        }
        return $ids;
    }
}
