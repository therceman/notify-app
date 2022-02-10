<?php

namespace App\Entity;

use OpenApi\Annotations as OA;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\NotificationRepository;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=NotificationRepository::class)
 * @ORM\HasLifecycleCallbacks
 */
class Notification
{
    const SMS_MAX_LENGTH = 140;
    const CHANNEL_EMAIL = 'email';
    const CHANNEL_SMS = 'sms';
    const CHANNEL_LIST = [self::CHANNEL_EMAIL, self::CHANNEL_SMS];

    public const GROUP__ADD = 'notification_add_group';
    public const GROUP__VIEW = 'notification_view_group';

    public const ERROR__BLANK = 'The value of the field can not be blank';
    public const ERROR__NULL = 'The value of the field can not be null';
    public const ERROR__WRONG_CLIENT_UUID = 'Wrong client ID format';
    public const ERROR__WRONG_CHANNEL = 'Choose a valid channel: sms or email';

    public const EXAMPLE__CHANNEL = self::CHANNEL_EMAIL;
    public const EXAMPLE__CONTENT = 'Hello';

    /**
     * @OA\Property(description="The unique identifier of the notification", example="1ec88cf6-c535-6950-86db-ddf1c337345e")
     * @ORM\Id
     * @ORM\Column(type="uuid", unique=true)
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator("doctrine.uuid_generator")
     * @Groups({self::GROUP__VIEW})
     */
    private $id;

    /**
     * @OA\Property(description="The unique identifier of the client", example="1ec88cf6-c535-6950-86db-ddf1c337345e")
     * @ORM\Column(type="uuid")
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator("doctrine.uuid_generator")
     * @Assert\Uuid(message=self::ERROR__WRONG_CLIENT_UUID, groups={self::GROUP__ADD}))
     * @Assert\NotNull(message=self::ERROR__NULL, groups={self::GROUP__ADD}))
     * @Assert\NotBlank(message=self::ERROR__BLANK, groups={self::GROUP__ADD})
     * @Groups({self::GROUP__ADD, self::GROUP__VIEW})
     * 
    */
    private $clientId;

    /**
     * @OA\Property(description="The channel - 'sms' or 'email'", example=self::CHANNEL_EMAIL)
     * @ORM\Column(type="string")
     * @Assert\Choice(choices=self::CHANNEL_LIST, message=self::ERROR__WRONG_CHANNEL, groups={self::GROUP__ADD}))
     * @Groups({self::GROUP__ADD, self::GROUP__VIEW})
     */
    private $channel;

    /**
     * @OA\Property(description="The content of notification", example="Hello")
     * @Assert\NotNull(message=self::ERROR__NULL, groups={self::GROUP__ADD}))
     * @Assert\NotBlank(message=self::ERROR__BLANK, groups={self::GROUP__ADD})
     * @ORM\Column(type="text")
     * @Groups({self::GROUP__ADD, self::GROUP__VIEW})
     */
    private $content;

    /**
     * @OA\Property(description="The date & time of created notification", example="2022-02-09T20:09:29+01:00")
     * @ORM\Column(type="datetime")
     * @Groups({self::GROUP__VIEW})
     */
    private $createdAt;

    /**
     * @OA\Property(description="Is notification processed?", example=false)
     * @ORM\Column(type="boolean")
     * @Groups({self::GROUP__VIEW})
     */
    private $isProcessed;

    /**
     * @OA\Property(description="The date & time when notification was processed", example="2022-02-09T20:19:29+01:00")
     * @ORM\Column(type="datetime", nullable=true)
     * @Groups({self::GROUP__VIEW})
     */
    private $processedAt;

    /**
     * Gets triggered only on insert

     * @ORM\PrePersist
     */
    public function onPrePersist()
    {
        $this->createdAt = new \DateTime("now");
        $this->isProcessed = false;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getChannel(): ?string
    {
        return $this->channel;
    }

    public function setChannel(string $channel): self
    {
        $this->channel = $channel;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getIsProcessed(): ?bool
    {
        return $this->isProcessed;
    }

    public function setIsProcessed(?bool $isProcessed): self
    {
        $this->isProcessed = $isProcessed;

        return $this;
    }

    public function getProcessedAt(): ?\DateTime
    {
        return $this->processedAt;
    }

    public function setProcessedAt(?\DateTime $processedAt): self
    {
        $this->processedAt = $processedAt;

        return $this;
    }

    public function getClientId(): string
    {
        return $this->clientId;
    }

    public function setClientId(string $clientId): self
    {
        $this->clientId = $clientId;

        return $this;
    }

    public static function example()
    {
        $client = new Notification();

        $client->setContent(self::EXAMPLE__CONTENT);
        $client->setChannel(self::EXAMPLE__CHANNEL);

        return $client;
    }
}
