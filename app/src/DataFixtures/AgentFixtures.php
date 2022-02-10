<?php

namespace App\DataFixtures;

use App\Entity\Agent;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Uid\Uuid;

class AgentFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $agent = new Agent();

        $agent->setUsername(Agent::EXAMPLE__USERNAME);
        $agent->setApiToken(Agent::EXAMPLE__AUTH_TOKEN);

        $manager->persist($agent);
        $manager->flush();
    }
}
