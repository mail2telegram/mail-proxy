<?php

namespace App\Client;

use App\App;
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

    // @todo draft
    public function draft(): bool
    {
        $account = App::get('testMailBox');
        $tel = new TelegramApiClient($this->logger);
        try {
            $mailbox = new Mailbox($account['imapPath'], $account['login'], $account['pwd']);
        } catch (InvalidParameterException $e) {
            $this->logger->error((string) $e);
            return false;
        }
        $mailsIds = $mailbox->searchMailbox('UNSEEN');
        foreach ($mailsIds as $id) {
            $mail = $mailbox->getMail($id);
            // @todo добавить <To>, возможно еще какие-то заголовки
            $msg = $mail->subject
                . "\n\nFrom: {$mail->fromName} <{$mail->fromAddress}>"
                . "\n\n{$mail->textPlain}";
            $tel->sendMessage($account['chatId'], $msg);
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
        return true;
    }
}
