<?php

namespace App\MessageHandler;

use App\Entity\Notification;
use App\Message\SendNotification;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use App\Repository\NotificationRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

final class SendNotificationHandler implements MessageHandlerInterface
{
    private $repository;
    private $mailer;
    private $doctrine;

    public function __construct(MailerInterface $mailer, NotificationRepository $repository, ManagerRegistry $doctrine)
    {
        $this->repository = $repository;
        $this->mailer = $mailer;
        $this->doctrine = $doctrine;
    }

    public function __invoke(SendNotification $message)
    {
        if ($message->getType() === Notification::CHANNEL_EMAIL)
            $this->handleEmail($message);

        if ($message->getType() === Notification::CHANNEL_SMS)
            $this->handleSMS($message);
    }

    private function handleSMS(SendNotification $message)
    {
        // do nothing (sending real sms is not implemented)

        $notification = $this->repository->find($message->getId());

        $notification->setProcessedAt(new \DateTime('now'))->setIsProcessed(true);

        $entityManager = $this->doctrine->getManager();
        $entityManager->persist($notification);
        $entityManager->flush();
    }

    private function handleEmail(SendNotification $message)
    {
        $templatedEmail = new TemplatedEmail();

        $email = $templatedEmail
            ->from('notify@app.com')
            ->to($message->getEmail())
            ->subject('New message {' . $message->getId() . '} from Notify App')
            ->text($message->getContent());

        $notification = $this->repository->find($message->getId());

        try {
            $this->mailer->send($email);
            $notification->setProcessedAt(new \DateTime('now'))->setIsProcessed(true);
        } catch (TransportExceptionInterface $e) {
            $notification->setProcessedAt(new \DateTime('now'));
        }

        $entityManager = $this->doctrine->getManager();
        $entityManager->persist($notification);
        $entityManager->flush();
    }
}
