<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\AgentRepository;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass=AgentRepository::class)
 * @UniqueEntity(fields="username")
 */
class Agent implements UserInterface
{
    const EXAMPLE__USERNAME = 'default_agent';
    const EXAMPLE__AUTH_TOKEN = 'd692dfe7657f7994c9eafe3b7914d252';

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=30, nullable=true)
     */
    private $username;

    /**
     * @ORM\Column(type="string", unique=true, length=32)
     */
    private $apiToken;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    public function getApiToken(): ?string
    {
        return $this->apiToken;
    }

    public function setApiToken(string $apiToken): self
    {
        $this->apiToken = $apiToken;

        return $this;
    }

    // UserInterface methods

    /**
     * Returns the roles granted to the user.
     * 
     * @return string[]
     */
    public function getRoles() {
        return ['ROLE_API_USER'];
    }

    /**
     * Returns the password used to authenticate the user.
     * 
     * @return string|null
     */
    public function getPassword(){
        return null;
    }

    /**
     * Returns the salt that was originally used to hash the password.
     *
     * @return string|null
     */
    public function getSalt() {
        return null;
    }

    /**
     * Removes sensitive data from the user.
     */
    public function eraseCredentials() {
        // do nothing
    }

    /**
     * Returns the identifier for this user (e.g. its username or email address)
     */
    public function getUserIdentifier()
    {
        return $this->apiToken;
    }
}
