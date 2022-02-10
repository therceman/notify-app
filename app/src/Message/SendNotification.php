<?php

namespace App\Message;

use App\Entity\Notification;

final class SendNotification
{

    private $email;
    private $id;
    private $content;
    private $phone;
    private $type;

    public function __construct(string $type, string $id, string $content, ?string $email, ?string $phone)
    {
        $this->id = $id;
        $this->type = $type;
        $this->email = $email;
        $this->phone = $phone;
        $this->content = $content;
    }

    public static function email(string $id, string $content, string $email) : SendNotification
    {
        return new self(Notification::CHANNEL_EMAIL, $id, $content, $email, null);
    }

    public static function sms(string $id, string $content, string $phone) : SendNotification
    {
        return new self(Notification::CHANNEL_SMS, $id, $content, null, $phone);
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getType(): string
    {
        return $this->type;
    }
}
