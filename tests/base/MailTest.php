<?php

/** @noinspection PhpIllegalPsrClassPathInspection PhpUnhandledExceptionInspection */

use Codeception\Test\Unit;
use PhpImap\Mailbox;

// https://stackoverflow.com/questions/32222250/connect-to-gmail-with-php-imap
// https://github.com/barbushin/php-imap
// https://www.php.net/manual/ru/ref.imap.php

class MailTest extends Unit
{
    protected BaseTester $tester;

    public function testMailParse(): void
    {
        $config = require './config.php';
        $testMailBox = $config['testMailBox'];
        $attachmentsDir = rtrim($config['attachmentsDir'], '/');
        if (!is_dir($attachmentsDir)) {
            mkdir($attachmentsDir, 0777, true);
        }

        $mailbox = new Mailbox($testMailBox['imapPath'], $testMailBox['login'], $testMailBox['pwd']);
        static::assertInstanceOf(Mailbox::class, $mailbox);

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
            codecept_debug('================================================================');
            codecept_debug($debug);
            if ($debug['hasAttachments']) {
                $attachments = $mail->getAttachments();
                foreach ($attachments as $attach) {
                    $path = $attachmentsDir . '/' . $attach->id . '_' . $attach->name;
                    $attach->setFilePath($path);
                    $attach->saveToDisk();
                    $debugAttach = (array) $attach;
                    codecept_debug(
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
                        ]
                    );
                }
            }
        }
    }
}
