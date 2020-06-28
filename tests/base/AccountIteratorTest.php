<?php

/** @noinspection PhpIllegalPsrClassPathInspection PhpUnhandledExceptionInspection */

use Codeception\Test\Unit;
use M2T\AccountIterator;
use M2T\AccountManager;
use M2T\App;
use M2T\Model\Account;
use M2T\Model\Email;

class AccountIteratorTest extends Unit
{
    protected BaseTester $tester;

    public function __construct()
    {
        parent::__construct();
        new App();
    }

    public function testBase(): void
    {
        /** @var AccountIterator $accounter */
        $accounter = App::get(AccountIterator::class);

        /** @var AccountManager $manager */
        $manager = App::get(AccountManager::class);

        $a = new Account(
            336724939,
            [
                new Email(
                    'mail2telegram.app@gmail.com',
                    'Otus1234',
                    'imap.gmail.com',
                    993,
                    'ssl',
                    'smtp.gmail.com',
                    465,
                    'ssl'
                ),
            ]
        );
        $manager->save($a);

        $result = $accounter->get();
        codecept_debug($result);
        static::assertInstanceOf(Account::class, $result);
    }
}
