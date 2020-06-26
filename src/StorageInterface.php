<?php

namespace M2T;

use M2T\Model\Account;

interface StorageInterface
{
    public function getAccount(): Account;
}
