<?php

namespace App\DataFixtures;

use App\Entity\Client;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ClientFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $client = new Client();

        $client->setFirstName(Client::EXAMPLE__FIRST_NAME);
        $client->setLastName(Client::EXAMPLE__LAST_NAME);
        $client->setEmail(Client::EXAMPLE__EMAIL);
        $client->setPhoneNumber(Client::EXAMPLE__PHONE_NUMBER);

        $manager->persist($client);

        $manager->flush();

        $this->addReference('example_client', $client);
    }
}
