<?php

namespace App;

use M2T\Model\Account;

interface StorageInterface
{
    public function getAccount(): Account;
}
