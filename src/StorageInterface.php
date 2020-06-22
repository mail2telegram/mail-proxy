<?php

namespace App;

use App\Model\Account;

interface StorageInterface
{
    public function getAccount(): Account;
}
