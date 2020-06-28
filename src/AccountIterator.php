<?php

namespace M2T;

use M2T\Model\Account;
use ArrayIterator;

class AccountIterator
{
    protected ArrayIterator $iterator;
    protected AccountManager $manager;

    public function __construct(AccountManager $manager)
    {
        $this->manager = $manager;
        $this->iterator = new ArrayIterator($this->getChats());
    }

    public function get(): ?Account
    {
        $current = $this->iterator->current();
        $this->iterator->next();
        if (!$this->iterator->valid()) {
            $this->iterator = new ArrayIterator($this->getChats());
        }
        return $current ? $this->manager->load($current) : null;
    }

    protected function getChats(): array
    {
        return $this->manager->getChats();
    }
}
