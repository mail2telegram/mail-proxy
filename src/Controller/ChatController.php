<?php

namespace App\Controller;

use App\ChatManager;
use App\Messenger\Telegram;

class ChatController
{
    public function handle()
    {
        $messenger = new Telegram();
        $message = $messenger->receiveMessage();
        (new ChatManager())->execute($message);
    }
}
