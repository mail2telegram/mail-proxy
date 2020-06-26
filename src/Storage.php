<?php

namespace M2T;

use M2T\Model\Account;
use ArrayIterator;

class Storage implements StorageInterface
{
    protected ArrayIterator $iterator;

    public function __construct()
    {
        $this->iterator = new ArrayIterator(App::get('test')['accounts']);
    }

    public function getAccount(): Account
    {
        $current = $this->iterator->current();
        $this->iterator->next();
        if (!$this->iterator->valid()) {
            $this->iterator->rewind();
        }
        return $current;
    }
}
