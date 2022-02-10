<?php

namespace App\Message;

final class SendEmailNotification
{

    private $email;
    private $id;
    private $content;

    public function __construct(string $id, string $email, string $content)
    {
        $this->id = $id;
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

    public function getId(): string
    {
        return $this->id;
    }
}
