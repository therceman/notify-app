<?php

namespace App\MessageHandler;

use App\Message\SendEmailNotification;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;

final class SendEmailNotificationHandler implements MessageHandlerInterface
{
    private $params;
    private $mailer;

    public function __construct(ContainerBagInterface $params, MailerInterface $mailer)
    {
        $this->params = $params;
        $this->mailer = $mailer;
    }

    public function __invoke(SendEmailNotification $message)
    {
        $templatedEmail = new TemplatedEmail();

        $email = $templatedEmail
            ->from($this->params->get('notify@app.com'))
            ->to($message->getEmail())
            ->subject('New message from Notify App')
            ->textTemplate($message->getContent());

        try {
            $this->mailer->send($email);
        } catch (TransportExceptionInterface $e) {
            
        }
    }
}
