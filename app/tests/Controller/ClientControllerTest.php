<?php

namespace App\Tests\Controller;

use App\Entity\Client;
use App\Utils\ApiJsonResponse;
use Doctrine\ORM\EntityManager;
use App\Controller\ClientController;
use App\Repository\ClientRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Serializer\Serializer;

class ClientControllerTest extends WebTestCase
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
     * @var ClientRepository
     */
    private $repository;

    /**
     * @var Serializer
     */
    private $serializer;

    protected function setUp(): void
    {
        $this->kernelClient = static::createClient();
        $this->entityManager = $this->kernelClient->getContainer()->get('doctrine.orm.entity_manager');
        $this->serializer = $this->kernelClient->getContainer()->get('serializer');
        $this->repository = $this->entityManager->getRepository(Client::class);
    }

    // -------------------- add() --------------------

    public function testAddOk(): void
    {
        $email_address = 'big.boy@bingo.com';

        $uniqueClient = Client::example()->setPhoneNumber('+37126081338')->setEmail($email_address);

        $clientData = $this->serializer->normalize($uniqueClient, null, ['groups' => Client::GROUP__VIEW]);

        $this->kernelClient->jsonRequest('POST', '/api/client', $clientData);
        $response = $this->kernelClient->getResponse();
        $result = json_decode($response->getContent(), true);

        $client = $this->repository->findOneBy(["email" => $email_address]);
        $response_client_id = $result[ApiJsonResponse::FIELD__CONTENT]['id'] ?? null;
        $clientData['id'] = $response_client_id;

        $this->assertNotNull($client);
        $this->assertSame($client->getId(), $response_client_id);
        $this->assertSameResponseOK($response, $clientData);

        $this->entityManager->remove($client);
        $this->entityManager->flush();
    }

    public function testAddValidationError(): void
    {
        $uniqueClient = Client::example()->setPhoneNumber('+37126081336')->setEmail('valid.email@bingo.com');

        $clientWithBlankEmail = (clone $uniqueClient)->setEmail('');
        $clientWithWrongEmail = (clone $uniqueClient)->setEmail('banana');
        $clientWithUsedEmail = (clone $uniqueClient)->setEmail(Client::EXAMPLE__EMAIL);

        $clientWithBlankPhoneNumber = (clone $uniqueClient)->setPhoneNumber('');
        $clientWithWrongPhoneNumber = (clone $uniqueClient)->setPhoneNumber('1234');
        $clientWithUsedPhoneNumber = (clone $uniqueClient)->setPhoneNumber(Client::EXAMPLE__PHONE_NUMBER);

        $clientWithShortFirstName = (clone $uniqueClient)->setFirstName('a');
        $clientWithLongFirstName = (clone $uniqueClient)->setFirstName('qweqweasdasdqweqweasdsadqweqweasd');
        $clientWithWrongFirstName = (clone $uniqueClient)->setFirstName('John123');

        $clientWithShortLastName = (clone $uniqueClient)->setLastName('a');
        $clientWithLongLastName = (clone $uniqueClient)->setLastName('qweqweasdasdqweqweasdsadqweqweasd');
        $clientWithWrongLastName = (clone $uniqueClient)->setLastName('Doe123');

        $this->assertAddBadValidation($clientWithBlankEmail, Client::ERROR__BLANK, "email");
        $this->assertAddBadValidation($clientWithWrongEmail, Client::ERROR__EMAIL_FORMAT, "email");
        $this->assertAddBadValidation($clientWithUsedEmail, Client::ERROR__EMAIL_UNIQUE, "email");

        $this->assertAddBadValidation($clientWithBlankPhoneNumber, Client::ERROR__BLANK, "phoneNumber");
        $this->assertAddBadValidation($clientWithWrongPhoneNumber, Client::ERROR__PHONE_NUMBER_FORMAT, "phoneNumber");
        $this->assertAddBadValidation($clientWithUsedPhoneNumber, Client::ERROR__PHONE_NUMBER_UNIQUE, "phoneNumber");

        $this->assertAddBadValidation($clientWithShortFirstName, Client::ERROR__TOO_SHORT, "firstName");
        $this->assertAddBadValidation($clientWithLongFirstName, Client::ERROR__TOO_LONG, "firstName");
        $this->assertAddBadValidation($clientWithWrongFirstName, Client::ERROR__FIRST_NAME_LATIN, "firstName");

        $this->assertAddBadValidation($clientWithShortLastName, Client::ERROR__TOO_SHORT, "lastName");
        $this->assertAddBadValidation($clientWithLongLastName, Client::ERROR__TOO_LONG, "lastName");
        $this->assertAddBadValidation($clientWithWrongLastName, Client::ERROR__LAST_NAME_LATIN, "lastName");
    }

    // -------------------- view() --------------------

    public function testViewWrongID(): void
    {
        $this->kernelClient->request('GET', '/api/client/123123');
        $response = $this->kernelClient->getResponse();

        $this->assertSameResponseError($response, ClientController::ERROR__WRONG_CLIENT_ID_FORMAT, Response::HTTP_BAD_REQUEST);
    }

    public function testViewNotFound(): void
    {
        $this->kernelClient->request('GET', '/api/client/1ec88d6c-ce3e-613e-be09-d70794779923');
        $response = $this->kernelClient->getResponse();

        $this->assertSameResponseError($response, ClientController::ERROR__CLIENT_NOT_FOUND, Response::HTTP_NOT_FOUND);
    }

    public function testViewOk(): void
    {
        $client = $this->repository->findOneBy(["email" => Client::EXAMPLE__EMAIL]);

        $client_array = $this->serializer->normalize($client, null, ['groups' => Client::GROUP__VIEW]);

        $this->kernelClient->request('GET', '/api/client/' . $client->getId());
        $response = $this->kernelClient->getResponse();

        $this->assertSameResponseOK($response, $client_array);
    }

    // -------------------- update() --------------------

    public function testUpdateValidationError(): void
    {
        $uniqueClient = $this->repository->findOneBy(["email" => Client::EXAMPLE__EMAIL]);

        $id = $uniqueClient->getId();

        $secondClient = new Client();

        $secondClient->setFirstName('Bob');
        $secondClient->setLastName('Biga');
        $secondClient->setEmail('biga.bob@bob.com');
        $secondClient->setPhoneNumber('+37126080827');

        $this->entityManager->persist($secondClient);
        $this->entityManager->flush();

        $clientWithWrongEmail = (clone $uniqueClient)->setEmail('banana');
        $clientWithUsedEmail = (clone $uniqueClient)->setEmail($secondClient->getEmail());

        $clientWithWrongPhoneNumber = (clone $uniqueClient)->setPhoneNumber('1234');
        $clientWithUsedPhoneNumber = (clone $uniqueClient)->setPhoneNumber($secondClient->getPhoneNumber());

        $clientWithShortFirstName = (clone $uniqueClient)->setFirstName('a');
        $clientWithLongFirstName = (clone $uniqueClient)->setFirstName('qweqweasdasdqweqweasdsadqweqweasd');
        $clientWithWrongFirstName = (clone $uniqueClient)->setFirstName('John123');

        $clientWithShortLastName = (clone $uniqueClient)->setLastName('a');
        $clientWithLongLastName = (clone $uniqueClient)->setLastName('qweqweasdasdqweqweasdsadqweqweasd');
        $clientWithWrongLastName = (clone $uniqueClient)->setLastName('Doe123');
    
        $this->assertUpdateBadValidation($id, $clientWithWrongEmail, Client::ERROR__EMAIL_FORMAT, "email");
        $this->assertUpdateBadValidation($id, $clientWithUsedEmail, Client::ERROR__EMAIL_UNIQUE, "email");

        $this->assertUpdateBadValidation($id, $clientWithWrongPhoneNumber, Client::ERROR__PHONE_NUMBER_FORMAT, "phoneNumber");
        $this->assertUpdateBadValidation($id, $clientWithUsedPhoneNumber, Client::ERROR__PHONE_NUMBER_UNIQUE, "phoneNumber");

        $this->assertUpdateBadValidation($id, $clientWithShortFirstName, Client::ERROR__TOO_SHORT, "firstName");
        $this->assertUpdateBadValidation($id, $clientWithLongFirstName, Client::ERROR__TOO_LONG, "firstName");
        $this->assertUpdateBadValidation($id, $clientWithWrongFirstName, Client::ERROR__FIRST_NAME_LATIN, "firstName");

        $this->assertUpdateBadValidation($id, $clientWithShortLastName, Client::ERROR__TOO_SHORT, "lastName");
        $this->assertUpdateBadValidation($id, $clientWithLongLastName, Client::ERROR__TOO_LONG, "lastName");
        $this->assertUpdateBadValidation($id, $clientWithWrongLastName, Client::ERROR__LAST_NAME_LATIN, "lastName");

        $secondClient = $this->repository->findOneBy(["email" => 'biga.bob@bob.com']);
        $this->entityManager->remove($secondClient);
        $this->entityManager->flush();
    }

    public function testUpdateOk(): void
    {
        $uniqueClient = $this->repository->findOneBy(["email" => Client::EXAMPLE__EMAIL]);

        $uniqueClient->setEmail('modified.email@mail.com');

        $clientData = $this->serializer->normalize($uniqueClient, null, ['groups' => Client::GROUP__VIEW]);

        $this->kernelClient->jsonRequest('PATCH', '/api/client/' . $uniqueClient->getId(), $clientData);
        $response = $this->kernelClient->getResponse();

        $client = $this->repository->findOneBy(["email" => 'modified.email@mail.com']);

        $this->assertNotNull($client);
        $this->assertSameResponseOK($response, $clientData);

        $client->setEmail(Client::EXAMPLE__EMAIL);
        $this->entityManager->persist($client);
        $this->entityManager->flush();
    }

    // -------------------- delete() --------------------

    public function testDeleteOk(): void
    {
        $newClient = Client::example();

        $newClient->setEmail('delete.me@gmail.com');
        $newClient->setPhoneNumber('+37126080891');

        $this->entityManager->persist($newClient);
        $this->entityManager->flush();

        $client = $this->repository->findOneBy(["email" => 'delete.me@gmail.com']);
        $this->assertNotNull($client);

        $this->kernelClient->jsonRequest('DELETE', '/api/client/' . $newClient->getId());
        $response = $this->kernelClient->getResponse();

        $client = $this->repository->findOneBy(["email" => 'delete.me@gmail.com']);
        $this->assertNull($client);
        $this->assertSameResponseOK($response, null);
    }

    private function assertAddBadValidation($client, $error_msg, $field)
    {
        $clientData = $this->serializer->normalize($client, null, ['groups' => Client::GROUP__VIEW]);

        $this->kernelClient->jsonRequest('POST', '/api/client', $clientData);
        $response = $this->kernelClient->getResponse();

        $this->assertSameResponseError($response, $error_msg, Response::HTTP_NOT_ACCEPTABLE, $field);
    }

    private function assertUpdateBadValidation($id, $client, $error_msg, $field)
    {
        $clientData = $this->serializer->normalize($client, null, ['groups' => Client::GROUP__VIEW]);

        $this->kernelClient->jsonRequest('PATCH', '/api/client/' . $id, $clientData);
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
