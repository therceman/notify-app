<?php

namespace App\Controller;

use App\Entity\Client;
use App\Entity\Notification;
use App\Utils\ApiJsonResponse;
use OpenApi\Annotations as OA;
use Symfony\Component\Uid\Uuid;
use App\Repository\ClientRepository;
use App\Message\SendNotification;
use Doctrine\Persistence\ManagerRegistry;
use Nelmio\ApiDocBundle\Annotation\Model;
use Symfony\Component\Messenger\Envelope;
use App\Repository\NotificationRepository;
use Doctrine\Common\Collections\ArrayCollection;
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
    public const BATCH_LIMIT = 10;

    public const ERROR__WRONG_ID_FORMAT = 'Wrong notification ID format';
    public const ERROR__WRONG_SMS_LENGTH = 'Content length for [sms] channel is too long';
    public const ERROR__CLIENT_NOT_FOUND = 'Notification client not found';
    public const ERROR__VALIDATION = 'Notification validation error';
    public const ERROR__NOT_FOUND = 'Notification not found';
    public const ERROR__BATCH_VALIDATION_FAILED = 'One or more notifications has errors';
    public const ERROR__BATCH_LIMIT = 'Max number of notifications per request reached';

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
     * View notification by ID
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
     * Get paginated list of notifications
     * 
     * @Route("/api/notifications", name="notification_list", methods={"GET"})
     * 
     * @OA\Parameter(
     *     name="page",
     *     in="query",
     *     description="Page number",
     *     example="1",
     *     required=false,
     *     @OA\Schema(type="int")
     * )
     * 
     * @OA\Parameter(
     *     name="limit",
     *     in="query",
     *     description="Page limit",
     *     example="10",
     *     required=false,
     *     @OA\Schema(type="int")
     * )
     * 
     * @OA\Parameter(
     *     name="client_id",
     *     in="query",
     *     description="Client Id <div><p><i>Example</i> : 1ec88cf6-c535-6950-86db-ddf1c337345e</p></div>",
     *     required=false,
     *     @OA\Schema(type="string")
     * )
     * 
     * @OA\Response(
     *     response=Response::HTTP_OK,
     *     description="Returns paginated list of notifications",
     *     @OA\JsonContent(type="array",
     *         @OA\Items(
     *             ref=@Model(type=Notification::class, groups={Notification::GROUP__VIEW})
     *         )
     *     )
     * )
     * 
     * @OA\Tag(name="Private API")
     * @Security(name="Bearer")
     */
    public function list(Request $request, NormalizerInterface $normalizer)
    {
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 10);
        $clientId = $request->query->get('client_id', null);

        if (null !== $clientId && false === Uuid::isValid($clientId)) {
            return ApiJsonResponse::error(Notification::ERROR__WRONG_CLIENT_UUID, Response::HTTP_NOT_ACCEPTABLE, 'clientId');
        }

        $criteria = (null !== $clientId) ? ['clientId' => $clientId] : [];

        $notifications = $this->repository->findBy($criteria, null, $limit, ($page - 1) * $limit);

        $list = [];

        foreach ($notifications as $notification) {
            $list[] = $normalizer->normalize($notification, null, ['groups' => Notification::GROUP__VIEW]);
        }

        return ApiJsonResponse::ok($list);
    }

    /**
     * Add a new notification
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

        $message = null;

        if (Notification::CHANNEL_EMAIL === $notification->getChannel()) {
            $message = SendNotification::email($notification->getId(), $notification->getContent(), $client->getEmail());
        }

        if (Notification::CHANNEL_SMS === $notification->getChannel()) {
            $message = SendNotification::sms($notification->getId(), $notification->getContent(), $client->getPhoneNumber());
        }

        if (null !== $message) {
            $envelope = new Envelope($message, [
                new AmqpStamp('normal')
            ]);
            $messageBus->dispatch($envelope);
        }

        $notification = $this->normalizer->normalize($notification, null, ['groups' => Notification::GROUP__VIEW]);

        return ApiJsonResponse::ok($notification);
    }

    /**
     * Add multiple notifications
     * 
     * @Route("/api/notification/batch", name="notification_batch", methods={"POST"})
     * 
     * @OA\RequestBody(
     *     @OA\JsonContent(type="array",
     *         @OA\Items(
     *             ref=@Model(type=Notification::class, groups={Notification::GROUP__ADD})
     *         )
     *     )
     * )
     * 
     * @OA\Response(
     *     response=Response::HTTP_OK,
     *     description="All Notifications were added successfully",
     *     @OA\JsonContent(type="object",
     *         @OA\Property(property=ApiJsonResponse::FIELD__STATUS, format="bool", default=true),
     *         @OA\Property(property=ApiJsonResponse::FIELD__CONTENT, type="object", default=null)
     *     )
     * )
     *
     * @OA\Response(
     *     response=Response::HTTP_REQUEST_ENTITY_TOO_LARGE,
     *     description=self::ERROR__BATCH_LIMIT,
     *     @OA\JsonContent(type="object",
     *         @OA\Property(property=ApiJsonResponse::FIELD__STATUS, format="bool", default=false),
     *         @OA\Property(property=ApiJsonResponse::FIELD__CONTENT, type="object",
     *             @OA\Property(property=ApiJsonResponse::FIELD__ERROR_MSG, type="string", default=self::ERROR__BATCH_LIMIT),
     *             @OA\Property(property=ApiJsonResponse::FIELD__ERROR_CODE, type="int", default=Response::HTTP_REQUEST_ENTITY_TOO_LARGE),
     *             @OA\Property(property=ApiJsonResponse::FIELD__INVALID_FIELD, type="string", default=null),
     *             @OA\Property(property=ApiJsonResponse::FIELD__ERROR_CONTENT, type="object",
     *                   @OA\Property(property="batch_limit", type="int", default=self::BATCH_LIMIT)
     *             )
     *         )
     *     )
     * )
     * 
     * @OA\Response(response=Response::HTTP_NOT_ACCEPTABLE, description=self::ERROR__BATCH_VALIDATION_FAILED, 
     *     @OA\JsonContent(type="object",
     *         @OA\Property(property=ApiJsonResponse::FIELD__STATUS, format="bool", default=false),
     *         @OA\Property(property=ApiJsonResponse::FIELD__CONTENT, type="object",
     *             @OA\Property(property=ApiJsonResponse::FIELD__ERROR_MSG, type="string", default=self::ERROR__BATCH_VALIDATION_FAILED),
     *             @OA\Property(property=ApiJsonResponse::FIELD__ERROR_CODE, type="int", default=Response::HTTP_NOT_ACCEPTABLE),
     *             @OA\Property(property=ApiJsonResponse::FIELD__INVALID_FIELD, type="string", default=null),
     *             @OA\Property(property=ApiJsonResponse::FIELD__ERROR_CONTENT, type="array", @OA\Items(type="object",
     *                  @OA\Property(property=ApiJsonResponse::FIELD__ERROR_MSG, type="string", default=self::ERROR__CLIENT_NOT_FOUND),
     *                  @OA\Property(property=ApiJsonResponse::FIELD__ERROR_CODE, type="int", default=Response::HTTP_NOT_ACCEPTABLE),
     *                  @OA\Property(property=ApiJsonResponse::FIELD__INVALID_FIELD, type="string", default="clientId"),
     *                  @OA\Property(property=ApiJsonResponse::FIELD__ERROR_CONTENT, type="object",
     *                      ref=@Model(type=Notification::class, groups={Notification::GROUP__ADD})
     *                  )
     *             ))
     *         )
     *     )
     * )
     * 
     * @OA\Tag(name="Private API")
     */
    public function batch(Request $request, SerializerInterface $serializer, NormalizerInterface $normalizer, ManagerRegistry $doctrine, ValidatorInterface $validator, MessageBusInterface $messageBus): Response
    {
        $notificationList = $serializer->deserialize($request->getContent(), 'App\Entity\Notification[]', 'json', ['groups' => Notification::GROUP__ADD]);

        $notificationErrors = [];
        $clientIdListForCheck = [];

        if (count($notificationList) > self::BATCH_LIMIT)
            return ApiJsonResponse::error(self::ERROR__BATCH_LIMIT, Response::HTTP_REQUEST_ENTITY_TOO_LARGE, null, [
                'batch_limit' => self::BATCH_LIMIT
            ]);
        
        // basic validation

        foreach ($notificationList as $notification) {
            $clientIdListForCheck[] = $notification->getClientId();

            $errors = $validator->validate($notification, null, [Notification::GROUP__ADD]);

            $errorMsg = null;
            $errorField = null;

            if (count($errors) > 0) {
                $errorMsg = $errors[0]->getMessage();
                $errorField = $errors[0]->getPropertyPath();
            }

            if (Notification::CHANNEL_SMS === $notification->getChannel() && strlen($notification->getContent()) > Notification::SMS_MAX_LENGTH) {
                $errorMsg = self::ERROR__WRONG_SMS_LENGTH;
                $errorField = 'content';
            }

            if (null !== $errorMsg)
                $notificationErrors[] = ApiJsonResponse::fromError(
                    $errorMsg,
                    Response::HTTP_NOT_ACCEPTABLE,
                    $errorField,
                    $normalizer->normalize($notification, null, ['groups' => Notification::GROUP__ADD])
                )->getData()['content'];
        }

        if (count($notificationErrors) > 0)
            return ApiJsonResponse::error(self::ERROR__BATCH_VALIDATION_FAILED, Response::HTTP_NOT_ACCEPTABLE, null, $notificationErrors);

        // client existance validation

        $clients = new ArrayCollection($doctrine->getManager()->getRepository(Client::class)->findBy(['id' => $clientIdListForCheck]));

        if (count($clients) !== count($clientIdListForCheck)) {
            foreach ($clientIdListForCheck as $clientId) {
                $foundClients = $clients->filter(function (Client $c) use ($clientId) {
                    return $c->getId() === $clientId;
                });

                if (0 === count($foundClients)) {
                    $notification = (new ArrayCollection($notificationList))->filter(function (Notification $not) use ($clientId) {
                        return $not->getClientId() === $clientId;
                    })->first();

                    $notificationErrors[] = ApiJsonResponse::fromError(
                        self::ERROR__CLIENT_NOT_FOUND,
                        Response::HTTP_NOT_ACCEPTABLE,
                        'clientId',
                        $normalizer->normalize($notification, null, ['groups' => Notification::GROUP__ADD])
                    )->getData()['content'];
                }
            }
        }

        if (count($notificationErrors) > 0)
            return ApiJsonResponse::error(self::ERROR__BATCH_VALIDATION_FAILED, Response::HTTP_NOT_ACCEPTABLE, null, $notificationErrors);

        // save to db 

        $entityManager = $doctrine->getManager();

        foreach ($notificationList as $notification) {
            $entityManager->persist($notification);
        }

        $entityManager->flush();

        // send messages

        foreach ($notificationList as $notification) {
            $client = $clients->filter(function (Client $c) use ($notification) {
                return $c->getId() === $notification->getClientId();
            })->first();

            $message = null;

            if (Notification::CHANNEL_EMAIL === $notification->getChannel()) {
                $message = SendNotification::email($notification->getId(), $notification->getContent(), $client->getEmail());
            }

            if (Notification::CHANNEL_SMS === $notification->getChannel()) {
                $message = SendNotification::sms($notification->getId(), $notification->getContent(), $client->getPhoneNumber());
            }

            if (null !== $message) {
                $envelope = new Envelope($message, [
                    new AmqpStamp('normal')
                ]);
                $messageBus->dispatch($envelope);
            }
        }

        return ApiJsonResponse::ok();
    }

    
}
