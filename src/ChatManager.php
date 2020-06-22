<?php

namespace App;

use App\Messenger\Messenger;

class ChatManager
{
    public function execute($message)
    {
        $repository = new Repository\AccountRepository();

        switch ($message) {
            case '/add':
                $repository->add();
                break;
            case '/delete':
                $repository->delete();
                break;
            case '/send':
                // добавить письмо в очередь на отправку
                break;
        }
    }
}
