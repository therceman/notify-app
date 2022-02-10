<?php

namespace App\Message;

final class SendEmailNotification
{

    private $email;

    public function __construct(string $email, string $content = 'hello')
    {
        $this->email = $email;
        $this->content = $content;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getContent(): string
    {
        return $this->content;
    }
}
