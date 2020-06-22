<?php

/** @noinspection PhpIllegalPsrClassPathInspection */
/** @noinspection PhpUnhandledExceptionInspection */

use App\App;
use Codeception\Test\Unit;
use PhpImap\Mailbox;

class ImapClientTest extends Unit
{
    protected BaseTester $tester;

    public function testMailParse(): void
    {
        new App();

        $attachmentsDir = rtrim(App::get('attachmentsDir'), '/');
        if (!is_dir($attachmentsDir)) {
            mkdir($attachmentsDir, 0777, true);
        }

        $testMailBox = App::get('testMailBox');
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
