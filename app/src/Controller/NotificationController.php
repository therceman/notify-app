<?php

namespace App\Controller;

use App\Entity\Client;
use App\Entity\Notification;
use App\Utils\ApiJsonResponse;
use OpenApi\Annotations as OA;
use Symfony\Component\Uid\Uuid;
use App\Repository\ClientRepository;
use App\Message\SendEmailNotification;
use Doctrine\Persistence\ManagerRegistry;
use Nelmio\ApiDocBundle\Annotation\Model;
use Symfony\Component\Messenger\Envelope;
use App\Repository\NotificationRepository;
use Nelmio\ApiDocBundle\Annotation\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpStamp;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class NotificationController extends AbstractController
{
    public const ERROR__WRONG_ID_FORMAT = 'Wrong notification ID format';
    public const ERROR__WRONG_SMS_LENGTH = 'Content length for [sms] channel is too long';
    public const ERROR__CLIENT_NOT_FOUND = 'Notification client not found';
    public const ERROR__VALIDATION = 'Notification validation error';
    public const ERROR__NOT_FOUND = 'Notification not found';

    /**
     * @var NotificationRepository
     */
    private $repository;

    /**
     * @var NormalizerInterface
     */
    private $normalizer;

    public function __construct(NotificationRepository $repository, NormalizerInterface $normalizer)
    {
        $this->repository = $repository;
        $this->normalizer = $normalizer;
    }

    /**
     * (Auth Required) View notification by ID
     * 
     * @Route("/api/notification/{id}", name="notification_view", methods={"GET"})
     * 
     * @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="The unique identifier of the notification",
     *     example="1ec88cf6-c535-6950-86db-ddf1c337345e",
     *     required=true,
     *     @OA\Schema(type="string")
     * )
     * 
     * @OA\Response(
     *     response=Response::HTTP_OK,
     *     description="Returns notification by ID",
     *     @OA\JsonContent(type="object",
     *         @OA\Property(property="status", format="bool", default=true),
     *         @OA\Property(property="content", ref=@Model(type=Notification::class, groups={Notification::GROUP__VIEW}))
     *     )
     * )
     * 
     * @OA\Response(response=Response::HTTP_BAD_REQUEST, description=self::ERROR__WRONG_ID_FORMAT, 
     *     @OA\JsonContent(type="object",
     *         @OA\Property(property=ApiJsonResponse::FIELD__STATUS, format="bool", default=false),
     *         @OA\Property(property=ApiJsonResponse::FIELD__CONTENT, type="object",
     *             @OA\Property(property=ApiJsonResponse::FIELD__ERROR_MSG, type="string", default=self::ERROR__WRONG_ID_FORMAT),
     *             @OA\Property(property=ApiJsonResponse::FIELD__ERROR_CODE, type="int", default=Response::HTTP_BAD_REQUEST),
     *             @OA\Property(property=ApiJsonResponse::FIELD__INVALID_FIELD, type="string", default=null)
     *         )
     *     )
     * )
     * 
     * @OA\Response(response=Response::HTTP_NOT_FOUND, description=self::ERROR__NOT_FOUND, 
     *     @OA\JsonContent(type="object",
     *         @OA\Property(property=ApiJsonResponse::FIELD__STATUS, format="bool", default=false),
     *         @OA\Property(property=ApiJsonResponse::FIELD__CONTENT, type="object",
     *             @OA\Property(property=ApiJsonResponse::FIELD__ERROR_MSG, type="string", default=self::ERROR__NOT_FOUND),
     *             @OA\Property(property=ApiJsonResponse::FIELD__ERROR_CODE, type="int", default=Response::HTTP_NOT_FOUND),
     *             @OA\Property(property=ApiJsonResponse::FIELD__INVALID_FIELD, type="string", default=null)
     *         )
     *     )
     * )
     * 
     * @OA\Tag(name="Private API")
     * @Security(name="Bearer")
     */
    public function view($id,  NormalizerInterface $normalizer): Response
    {
        if (false === Uuid::isValid($id))
            return ApiJsonResponse::error(self::ERROR__WRONG_ID_FORMAT, Response::HTTP_BAD_REQUEST);

        $notification = $this->repository->find($id);

        if (null === $notification)
            return ApiJsonResponse::error(self::ERROR__NOT_FOUND, Response::HTTP_NOT_FOUND);

        $notification = $normalizer->normalize($notification, null, ['groups' => Notification::GROUP__VIEW]);

        return ApiJsonResponse::ok($notification);
    }

    /**
     * (Auth Required) Add a new notification
     * 
     * @Route("/api/notification", name="notification_add", methods={"POST"})
     * 
     * @OA\RequestBody(
     *     @OA\MediaType(mediaType="application/json",
     *         @OA\Schema(ref=@Model(type=Notification::class, groups={Notification::GROUP__ADD}))
     *     )
     * )
     * 
     * @OA\Response(
     *     response=Response::HTTP_OK,
     *     description="Notification added successfully",
     *     @OA\JsonContent(type="object",
     *         @OA\Property(property=ApiJsonResponse::FIELD__STATUS, format="bool", default=true),
     *         @OA\Property(property=ApiJsonResponse::FIELD__CONTENT, type="object",
     *             ref=@Model(type=Notification::class, groups={Notification::GROUP__VIEW})
     *         )
     *     )
     * )
     * 
     * @OA\Response(response=Response::HTTP_NOT_ACCEPTABLE, description=self::ERROR__VALIDATION, 
     *     @OA\JsonContent(type="object",
     *         @OA\Property(property=ApiJsonResponse::FIELD__STATUS, format="bool", default=false),
     *         @OA\Property(property=ApiJsonResponse::FIELD__CONTENT, type="object",
     *             @OA\Property(property=ApiJsonResponse::FIELD__ERROR_MSG, type="string", default=Notification::ERROR__BLANK),
     *             @OA\Property(property=ApiJsonResponse::FIELD__ERROR_CODE, type="int", default=Response::HTTP_NOT_ACCEPTABLE),
     *             @OA\Property(property=ApiJsonResponse::FIELD__INVALID_FIELD, type="string", default="email")
     *         )
     *     )
     * )
     * 
     * @OA\Tag(name="Private API")
     */
    public function add(Request $request, SerializerInterface $serializer, ManagerRegistry $doctrine, ValidatorInterface $validator, MessageBusInterface $messageBus): Response
    {
        /** @var Notification */
        $notification = $serializer->deserialize($request->getContent(), Notification::class, 'json', ['groups' => Notification::GROUP__ADD]);

        $errors = $validator->validate($notification, null, [Notification::GROUP__ADD]);
        if (count($errors) > 0) {
            return ApiJsonResponse::error($errors[0]->getMessage(), Response::HTTP_NOT_ACCEPTABLE, $errors[0]->getPropertyPath());
        }

        if (Notification::CHANNEL_SMS === $notification->getChannel() && strlen($notification->getContent()) > Notification::SMS_MAX_LENGTH) {
            return ApiJsonResponse::error(self::ERROR__WRONG_SMS_LENGTH, Response::HTTP_NOT_ACCEPTABLE, "content");
        }

        $client = $doctrine->getManager()->getRepository(Client::class)->find($notification->getClientId());
    
        if (null === $client) {
            return ApiJsonResponse::error(self::ERROR__CLIENT_NOT_FOUND, Response::HTTP_NOT_ACCEPTABLE, "clientId");
        }

        $entityManager = $doctrine->getManager();
        $entityManager->persist($notification);
        $entityManager->flush();

        if (Notification::CHANNEL_EMAIL === $notification->getChannel()) {
            $emailMessage = new SendEmailNotification($client->getEmail(), $notification->getContent());
            $envelope = new Envelope($emailMessage, [
                new AmqpStamp('normal')
            ]);
            $messageBus->dispatch($envelope);
        }

        $notification = $this->normalizer->normalize($notification, null, ['groups' => Notification::GROUP__VIEW]);

        return ApiJsonResponse::ok($notification);
    }
}
