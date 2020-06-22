<?php

namespace App\Model;

class Account
{
    public string $imapPath;
    public string $imapLogin;
    public string $imapPassword;
    public int $telegramChatId;

    public function __construct(string $imapPath, string $impaLogin, string $imapPassword, int $telegramChatId)
    {
        $this->imapPath = $imapPath;
        $this->imapLogin = $impaLogin;
        $this->imapPassword = $imapPassword;
        $this->telegramChatId = $telegramChatId;
    }
}
