<?php

/** @noinspection PhpUnhandledExceptionInspection */

namespace Unit;

use UnitTester;
use Codeception\Test\Unit;
use M2T\AccountIterator;
use M2T\AccountManager;
use M2T\App;
use M2T\Model\Account;

class AccountIteratorTest extends Unit
{
    protected UnitTester $tester;

    public function testBase(): void
    {
        /** @var AccountIterator $accounter */
        $accounter = App::get(AccountIterator::class);

        /** @var AccountManager $manager */
        $manager = App::get(AccountManager::class);

        $manager->save($this->tester->accountProvider());
        $result = $accounter->get();
        static::assertInstanceOf(Account::class, $result);
    }
}
