<?php

namespace App\Entity;

use OpenApi\Annotations as OA;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\ClientRepository;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Misd\PhoneNumberBundle\Validator\Constraints\PhoneNumber as AssertPhoneNumber;

/**
 * @ORM\Entity(repositoryClass=ClientRepository::class)
 * @UniqueEntity(fields="email", message=Client::ERROR__EMAIL_UNIQUE, groups={self::GROUP__ADD, self::GROUP__UPDATE})
 * @UniqueEntity(fields="phoneNumber",  message=Client::ERROR__PHONE_NUMBER_UNIQUE, groups={self::GROUP__ADD, self::GROUP__UPDATE})
 */
class Client
{

    public const GROUP__ADD = 'client_add_group';
    public const GROUP__UPDATE = 'client_update_group';
    public const GROUP__VIEW = 'client_view_group';

    public const EXAMPLE__FIRST_NAME = 'John';
    public const EXAMPLE__LAST_NAME = 'Doe';
    public const EXAMPLE__EMAIL = 'john.doe@mail.com';
    public const EXAMPLE__PHONE_NUMBER = '+37126081337';

    public const ERROR__BLANK = 'The value of the field can not be blank';
    public const ERROR__NULL = 'The value of the field can not be null';
    public const ERROR__TOO_SHORT = 'The value of the field is too short';
    public const ERROR__TOO_LONG = 'The value of the field is too long';
    public const ERROR__EMAIL_UNIQUE = 'Provided email address already in use';
    public const ERROR__PHONE_NUMBER_UNIQUE = 'Provided phone number already in use';
    public const ERROR__EMAIL_FORMAT = 'Wrong email format provided';
    public const ERROR__PHONE_NUMBER_FORMAT = 'Wrong phone number format provided';
    public const ERROR__FIRST_NAME_LATIN = 'First name can only contain latin letters without spaces';
    public const ERROR__LAST_NAME_LATIN = 'Last name can only contain latin letters without spaces';

    /**
     * @OA\Property(description="The unique identifier of the client", example="1ec88cf6-c535-6950-86db-ddf1c337345e")
     * @ORM\Id
     * @ORM\Column(type="uuid", unique=true)
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator("doctrine.uuid_generator")
     * @Groups({self::GROUP__ADD})
     */
    private $id;

    /**
     * @OA\Property(description="First name", example=self::EXAMPLE__FIRST_NAME)
     * @ORM\Column(type="string", length=32)
     * @Assert\NotNull(message=self::ERROR__NULL, groups={self::GROUP__ADD, self::GROUP__UPDATE}))
     * @Assert\NotBlank(message=self::ERROR__BLANK, groups={self::GROUP__ADD})
     * @Assert\Length(
     *      min=2,
     *      max=32,
     *      minMessage=self::ERROR__TOO_SHORT,
     *      maxMessage=self::ERROR__TOO_LONG,
     *      groups={self::GROUP__ADD, self::GROUP__UPDATE}
     * )
     * @Assert\Regex(
     *     pattern="/^[a-zA-Z]+$/",
     *     match=true,
     *     message=self::ERROR__FIRST_NAME_LATIN,
     *     groups={self::GROUP__ADD, self::GROUP__UPDATE}
     * )
     * @Groups({self::GROUP__ADD, self::GROUP__VIEW})
     */
    private $firstName;

    /**
     * @OA\Property(description="Last name", example=self::EXAMPLE__LAST_NAME)
     * @ORM\Column(type="string", length=32)
     * @Assert\NotNull(message=self::ERROR__NULL, groups={self::GROUP__ADD, self::GROUP__UPDATE}))
     * @Assert\NotBlank(message=self::ERROR__BLANK, groups={self::GROUP__ADD})
     * @Assert\Length(
     *      min=2,
     *      max=32,
     *      minMessage=self::ERROR__TOO_SHORT,
     *      maxMessage=self::ERROR__TOO_LONG,
     *      groups={self::GROUP__ADD, self::GROUP__UPDATE}
     * )
     * @Assert\Regex(
     *     pattern="/^[a-zA-Z]+$/",
     *     match=true,
     *     message=self::ERROR__LAST_NAME_LATIN,
     *     groups={self::GROUP__ADD, self::GROUP__UPDATE}
     * )
     * @Groups({self::GROUP__ADD, self::GROUP__VIEW})
     */
    private $lastName;

    /**
     * @OA\Property(description="Email address", example=self::EXAMPLE__EMAIL)
     * @ORM\Column(type="string", length=255, unique=true)
     * @Assert\NotNull(message=self::ERROR__NULL, groups={self::GROUP__ADD, self::GROUP__UPDATE}))
     * @Assert\NotBlank(message=self::ERROR__BLANK, groups={self::GROUP__ADD})
     * @Assert\Email(message=self::ERROR__EMAIL_FORMAT, groups={self::GROUP__ADD, self::GROUP__UPDATE})
     * @Assert\Length(max=255, maxMessage=self::ERROR__TOO_LONG, groups={self::GROUP__ADD, self::GROUP__UPDATE})
     * @Groups({self::GROUP__ADD, self::GROUP__VIEW})
     */
    private $email;

    /**
     * @OA\Property(description="Phone number", example=self::EXAMPLE__PHONE_NUMBER)
     * @ORM\Column(type="string", length=16, unique=true)
     * @Assert\NotNull(message=self::ERROR__NULL, groups={self::GROUP__ADD, self::GROUP__UPDATE}))
     * @Assert\NotBlank(message=self::ERROR__BLANK, groups={self::GROUP__ADD})
     * @AssertPhoneNumber(message=self::ERROR__PHONE_NUMBER_FORMAT, groups={self::GROUP__ADD, self::GROUP__UPDATE})
     * @Assert\Length(max=16, maxMessage=self::ERROR__TOO_LONG, groups={self::GROUP__ADD, self::GROUP__UPDATE})
     * @Groups({self::GROUP__ADD, self::GROUP__VIEW})
     */
    private $phoneNumber;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): self
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): self
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(?string $phoneNumber): self
    {
        $this->phoneNumber = $phoneNumber;

        return $this;
    }

    public static function example()
    {
        $client = new Client();

        $client->setFirstName(self::EXAMPLE__FIRST_NAME);
        $client->setLastName(self::EXAMPLE__LAST_NAME);
        $client->setEmail(self::EXAMPLE__EMAIL);
        $client->setPhoneNumber(self::EXAMPLE__PHONE_NUMBER);

        return $client;
    }
}
