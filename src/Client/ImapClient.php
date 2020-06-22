<?php

namespace App\Client;

// https://stackoverflow.com/questions/32222250/connect-to-gmail-with-php-imap
// https://github.com/barbushin/php-imap
// https://www.php.net/manual/ru/ref.imap.php

use App\App;
use App\Util\OFile;
use PhpImap\Exceptions\InvalidParameterException;
use PhpImap\IncomingMail;
use PhpImap\IncomingMailAttachment;
use PhpImap\Mailbox;
use Psr\Log\LoggerInterface;

class ImapClient
{
    protected LoggerInterface $logger;
    protected TelegramClient $telegram;
    protected string $attachmentsDir;

    public function __construct(LoggerInterface $logger, TelegramClient $telegram, string $attachmentsDir = '')
    {
        $this->logger = $logger;
        $this->telegram = $telegram;
        $this->attachmentsDir = $attachmentsDir ?: rtrim(App::get('attachmentsDir'), '/');
    }

    // @todo draft
    public function draft(): bool
    {
        $account = App::get('testMailBox');
        try {
            $mailbox = new Mailbox($account['imapPath'], $account['login'], $account['pwd']);
        } catch (InvalidParameterException $e) {
            $this->logger->error((string) $e);
            return false;
        }
        $this->forwardMailsToTelegram($mailbox, $account['chatId']);
        return true;
    }

    public function forwardMailsToTelegram(Mailbox $mailbox, int $chatId): void
    {

        $mailsIds = $mailbox->searchMailbox('UNSEEN');
        foreach ($mailsIds as $id) {
            $mail = $mailbox->getMail($id);
            // @todo разбить на чанки, максимум 4096 символов
            $this->telegram->sendMessage($chatId, static::format($mail));
            $this->logger->debug(
                print_r(
                    [
                        'id' => $mail->id,
                        'date' => $mail->date,
                        'fromName' => $mail->fromName,
                        'fromAddress' => $mail->fromAddress,
                        'subject' => $mail->subject,
                        'hasAttachments' => (int) $mail->hasAttachments(),
                        'textPlain' => $mail->textPlain,
                    ],
                    true
                )
            );
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
        $path = $this->attachmentsDir . '/' . $attach->id . '_' . $attach->name;
        $attach->setFilePath($path);

        // @todo отправка файла без сохранения на диск
        $attach->saveToDisk();

        // @todo проверка размера - 10 MB max size for photos, 50 MB for other files
        $this->telegram->sendDocument($chatId, $path);

        $debugAttach = (array) $attach;
        $this->logger->debug(
            print_r(
                [
                    'id' => $debugAttach['id'],
                    'contentId' => $debugAttach['contentId'],
                    'type' => $debugAttach['type'],
                    'encoding' => $debugAttach['encoding'],
                    'subtype' => $debugAttach['subtype'],
                    'description' => $debugAttach['description'],
                    'name' => $debugAttach['name'],
                    'sizeInBytes' => $debugAttach['sizeInBytes'],
                    'disposition' => $debugAttach['disposition'],
                    'charset' => $debugAttach['charset'],
                    'mime' => $debugAttach['mime'],
                    'mimeEncoding' => $debugAttach['mimeEncoding'],
                    'fileExtension' => $debugAttach['fileExtension'],
                ],
                true
            )
        );
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
