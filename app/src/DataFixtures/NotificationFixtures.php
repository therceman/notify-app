<?php

namespace App\DataFixtures;

use App\Entity\Client;
use App\Entity\Notification;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class NotificationFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $notification = new Notification();
        $notification->setChannel(Notification::EXAMPLE__CHANNEL);
        $notification->setContent(Notification::EXAMPLE__CONTENT);
        $notification->setClientId($this->getReference('example_client')->getId());
        $manager->persist($notification);
        $manager->flush();
    }
}
