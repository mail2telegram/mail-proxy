<?php

/** @noinspection PhpUnhandledExceptionInspection */

namespace Unit;

use Codeception\Test\Unit;
use M2T\AccountIterator;
use M2T\AccountManager;
use UnitTester;

class AccountIteratorTest extends Unit
{
    protected UnitTester $tester;

    public function testBase(): void
    {
        [$account0, $account1] = $this->tester->accountProvider();
        $account = &$account0;

        $manager = $this->createStub(AccountManager::class);
        $manager->method('getChats')->willReturn([$account0->chatId, $account1->chatId]);
        $manager->method('load')->willReturn($account0, $account1, $account0);
        $accounter = new AccountIterator($manager);

        $result = $accounter->get();
        static::assertSame($account->chatId, $result->chatId);

        $account = &$account1;
        $result = $accounter->get();
        static::assertSame($account->chatId, $result->chatId);

        $account = &$account0;
        $result = $accounter->get();
        static::assertSame($account->chatId, $result->chatId);
    }
}
