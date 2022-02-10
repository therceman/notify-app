<?php

namespace App\Tests\Controller;

use App\Entity\Agent;
use App\Entity\Client;
use App\Entity\Notification;
use App\Utils\ApiJsonResponse;
use Doctrine\ORM\EntityManager;
use App\Repository\ClientRepository;
use App\Controller\NotificationController;
use App\Repository\NotificationRepository;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Uid\Uuid;

class NotificationControllerTest extends WebTestCase
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var KernelBrowser
     */
    private $kernelClient;

    /**
     * @var NotificationRepository
     */
    private $repository;

    /**
     * @var ClientRepository
     */
    private $clientRepository;

    /**
     * @var Serializer
     */
    private $serializer;

    protected function setUp(): void
    {
        $this->kernelClient = static::createClient();
        $this->entityManager = $this->kernelClient->getContainer()->get('doctrine.orm.entity_manager');
        $this->serializer = $this->kernelClient->getContainer()->get('serializer');
        $this->repository = $this->entityManager->getRepository(Notification::class);
        $this->clientRepository = $this->entityManager->getRepository(Client::class);

        // Authorize All Requests
        $this->kernelClient->setServerParameter('HTTP_AUTHORIZATION', 'Bearer ' . Agent::EXAMPLE__AUTH_TOKEN);
    }

    // -------------------- add() --------------------

    public function testAddOk(): void
    {
        $content = 'message_' . Uuid::v4();

        $client = $this->clientRepository->findOneBy(['email' => Client::EXAMPLE__EMAIL]);

        $notification = Notification::example()->setContent($content)->setClientId($client->getId());

        $data = $this->serializer->normalize($notification, null, ['groups' => Notification::GROUP__ADD]);

        $this->kernelClient->jsonRequest('POST', '/api/notification', $data);
        $response = $this->kernelClient->getResponse();
        $result = json_decode($response->getContent(), true);

        $response_notification_id = $result[ApiJsonResponse::FIELD__CONTENT]['id'] ?? null;

        $new_notification = $this->repository->find(["id" => $response_notification_id]);

        $this->assertNotNull($new_notification);
        $this->assertSame($new_notification->getContent(), $result[ApiJsonResponse::FIELD__CONTENT]['content'] ?? null);

        $this->entityManager->remove($new_notification);
        $this->entityManager->flush();
    }

    public function testAddValidationError(): void
    {
        $client = $this->clientRepository->findOneBy(['email' => Client::EXAMPLE__EMAIL]);

        $notification = Notification::example()->setClientId($client->getId());

        $notWithBlankClientId = (clone $notification)->setClientId('');
        $notWithWrongClientId = (clone $notification)->setClientId('123');
        $notWithNonExistingClientId = (clone $notification)->setClientId('1ec8a443-e31c-646e-a8ba-374d07cbb44a');

        $notWithBlankChannel = (clone $notification)->setChannel('');
        $notWithWrongChannel = (clone $notification)->setChannel('banana');

        $notWithBlankContent = (clone $notification)->setContent('');
        $notWithSMSGtMaxLen = (clone $notification)->setChannel(Notification::CHANNEL_SMS)->setContent(str_repeat("A", 141));

        $this->assertAddBadValidation($notWithBlankClientId, Notification::ERROR__BLANK, "clientId");
        $this->assertAddBadValidation($notWithWrongClientId, Notification::ERROR__WRONG_CLIENT_UUID, "clientId");
        $this->assertAddBadValidation($notWithNonExistingClientId, NotificationController::ERROR__CLIENT_NOT_FOUND, "clientId");

        $this->assertAddBadValidation($notWithBlankChannel, Notification::ERROR__WRONG_CHANNEL, "channel");
        $this->assertAddBadValidation($notWithWrongChannel, Notification::ERROR__WRONG_CHANNEL, "channel");

        $this->assertAddBadValidation($notWithBlankContent, Notification::ERROR__BLANK, "content");
        $this->assertAddBadValidation($notWithSMSGtMaxLen, NotificationController::ERROR__WRONG_SMS_LENGTH, "content");
    }

    // -------------------- view() --------------------

    public function testViewWrongID(): void
    {
        $this->kernelClient->request('GET', '/api/notification/123123');

        $response = $this->kernelClient->getResponse();

        $this->assertSameResponseError($response, NotificationController::ERROR__WRONG_ID_FORMAT, Response::HTTP_BAD_REQUEST);
    }

    public function testViewNotFound(): void
    {
        $this->kernelClient->request('GET', '/api/notification/1ec88d6c-ce3e-613e-be09-d70794779923');
        $response = $this->kernelClient->getResponse();

        $this->assertSameResponseError($response, NotificationController::ERROR__NOT_FOUND, Response::HTTP_NOT_FOUND);
    }

    public function testViewOk(): void
    {
        $notification = $this->repository->findOneBy(["content" => Notification::EXAMPLE__CONTENT]);

        $notification_array = $this->serializer->normalize($notification, null, ['groups' => Notification::GROUP__VIEW]);

        $this->kernelClient->request('GET', '/api/notification/' . $notification->getId());
        $response = $this->kernelClient->getResponse();

        $this->assertSameResponseOK($response, $notification_array);
    }

    // -------------------- update() --------------------

    // TODO

    // -------------------- delete() --------------------

    // TODO

    private function assertAddBadValidation($notification, $error_msg, $field)
    {
        $data = $this->serializer->normalize($notification, null, ['groups' => Notification::GROUP__ADD]);

        $this->kernelClient->jsonRequest('POST', '/api/notification', $data);
        $response = $this->kernelClient->getResponse();

        $this->assertSameResponseError($response, $error_msg, Response::HTTP_NOT_ACCEPTABLE, $field);
    }

    private function assertSameResponseError(Response $response, string $msg, int $status_code, ?string $invalid_field = null)
    {
        $result = json_decode($response->getContent(), true);

        $content = $result[ApiJsonResponse::FIELD__CONTENT] ?? [];

        $this->assertSame(false, $result[ApiJsonResponse::FIELD__STATUS] ?? null);
        $this->assertSame($status_code, $content[ApiJsonResponse::FIELD__ERROR_CODE] ?? null);
        $this->assertSame($invalid_field, $content[ApiJsonResponse::FIELD__INVALID_FIELD] ?? null);
        $this->assertSame($msg, $content[ApiJsonResponse::FIELD__ERROR_MSG] ?? null);

        $this->assertSame($status_code, $response->getStatusCode());
    }

    private function assertSameResponseOK(Response $response, ?array $content)
    {
        $result = json_decode($response->getContent(), true);

        $this->assertSame(true, $result[ApiJsonResponse::FIELD__STATUS] ?? null);
        $this->assertEquals($content, $result[ApiJsonResponse::FIELD__CONTENT] ?? null);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }
}
