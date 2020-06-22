<?php

namespace App\Client;

use App\App;
use App\Model\Account;
use PhpImap\Exceptions\InvalidParameterException;
use PhpImap\IncomingMail;
use PhpImap\IncomingMailAttachment;
use PhpImap\Mailbox;
use Psr\Log\LoggerInterface;

/**
 * Class ImapClient
 * @package App\Client
 * @todo уйти от связанности с классом TelegramClient. Пусть ImapClient занимается чем-то одним,
 * а именно получает почту из ящика. Отправлять её в телеграм должен кто-то другой (думаю что Worker).
 */
class ImapClient
{
    public const MAX_FILE_SIZE = 10_485_760; // 10 MB

    protected LoggerInterface $logger;
    protected TelegramClient $telegram;

    public function __construct(LoggerInterface $logger, TelegramClient $telegram)
    {
        $this->logger = $logger;
        $this->telegram = $telegram;
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

    public function forwardMailsToTelegram(Mailbox $mailbox, int $chatId): void
    {
        $mailsIds = $mailbox->searchMailbox('UNSEEN');
        foreach ($mailsIds as $id) {
            $mail = $mailbox->getMail($id);
            $this->telegram->sendMessage($chatId, static::format($mail));
            if (App::get('env') !== 'prod') {
                $this->logger->debug('Message: ' . static::format($mail));
            }
            if ($mail->hasAttachments()) {
                $attachments = $mail->getAttachments();
                foreach ($attachments as $attach) {
                    $this->sendAttachmentToTelegram($attach, $chatId);
                }
            }
        }
    }

    public function sendAttachmentToTelegram(IncomingMailAttachment $attach, int $chatId): void
    {
        if ($attach->sizeInBytes >= static::MAX_FILE_SIZE) {
            $msg = "File '{$attach->name}' too big";
            $this->telegram->sendMessage($chatId, $msg);
            return;
        }
        $this->telegram->sendDocument($chatId, $attach->name, $attach->getContents());
    }

    protected static function format(IncomingMail $mail): string
    {
        return $mail->subject
            . "\n\nDate: {$mail->date}"
            . "\nTo: {$mail->toString}"
            . "\nFrom: {$mail->fromName} <{$mail->fromAddress}>"
            . "\n\n{$mail->textPlain}";
    }
}
