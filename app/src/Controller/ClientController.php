<?php

namespace App\Controller;

use App\RequestHandler\ApiRequestHandler;
use App\Entity\Client;
use App\Utils\ApiJsonResponse;
use OpenApi\Annotations as OA;
use Symfony\Component\Uid\Uuid;
use App\Repository\ClientRepository;
use Doctrine\Persistence\ManagerRegistry;
use Nelmio\ApiDocBundle\Annotation\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

class ClientController extends AbstractController
{
    public const ERROR__WRONG_CLIENT_ID_FORMAT = 'Wrong client ID format';
    public const ERROR__CLIENT_NOT_FOUND = 'Client not found';
    public const ERROR__CLIENT_VALIDATION = 'Client validation error';

    /**
     * @var ClientRepository
     */
    private $repository;
    
    /** 
     * @var ApiRequestHandler
     */
    private $requestHandler;

    public function __construct(ClientRepository $clientRepository, ApiRequestHandler $apiRequestHandler)
    {
        $this->repository = $clientRepository;
        $this->requestHandler = $apiRequestHandler;
    }

    /**
     * Get paginated list of clients
     * 
     * @Route("/api/clients", name="client_list", methods={"GET"})
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
     * @OA\Response(
     *     response=Response::HTTP_OK,
     *     description="Returns paginated list of clients",
     *     @OA\JsonContent(type="array",
     *         @OA\Items(
     *             ref=@Model(type=Client::class, groups={Client::GROUP__ADD})
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

        $clients = $this->repository->findBy([], null, $limit, ($page - 1) * $limit);

        $list = [];

        foreach ($clients as $client) {
            $list[] = $normalizer->normalize($client, null, ['groups' => Client::GROUP__ADD]);
        }

        return ApiJsonResponse::ok($list);
    }

    /**
     * Add a new client
     * 
     * @Route("/api/client", name="client_add", methods={"POST"})
     * 
     * @OA\RequestBody(
     *     @OA\MediaType(mediaType="application/json",
     *         @OA\Schema(ref=@Model(type=Client::class, groups={Client::GROUP__VIEW}))
     *     )
     * )
     * 
     * @OA\Response(
     *     response=Response::HTTP_OK,
     *     description="Client added successfully",
     *     @OA\JsonContent(type="object",
     *         @OA\Property(property=ApiJsonResponse::FIELD__STATUS, format="bool", default=true),
     *         @OA\Property(property=ApiJsonResponse::FIELD__CONTENT, type="object",
     *             ref=@Model(type=Client::class, groups={Client::GROUP__ADD})
     *         )
     *     )
     * )
     * 
     * @OA\Response(response=Response::HTTP_NOT_ACCEPTABLE, description=self::ERROR__CLIENT_VALIDATION, 
     *     @OA\JsonContent(type="object",
     *         @OA\Property(property=ApiJsonResponse::FIELD__STATUS, format="bool", default=false),
     *         @OA\Property(property=ApiJsonResponse::FIELD__CONTENT, type="object",
     *             @OA\Property(property=ApiJsonResponse::FIELD__ERROR_MSG, type="string", default=Client::ERROR__BLANK),
     *             @OA\Property(property=ApiJsonResponse::FIELD__ERROR_CODE, type="int", default=Response::HTTP_NOT_ACCEPTABLE),
     *             @OA\Property(property=ApiJsonResponse::FIELD__INVALID_FIELD, type="string", default="email")
     *         )
     *     )
     * )
     * 
     * @OA\Tag(name="Public API")
     */
    public function add(Request $request, NormalizerInterface $normalizer, ManagerRegistry $doctrine, ValidatorInterface $validator): Response
    {
        $client = $this->requestHandler->createEntity($request, Client::class, Client::GROUP__VIEW);

        $errors = $validator->validate($client, null, [Client::GROUP__ADD]);
        if (count($errors) > 0) {
            return ApiJsonResponse::error($errors[0]->getMessage(), Response::HTTP_NOT_ACCEPTABLE, $errors[0]->getPropertyPath());
        }

        $entityManager = $doctrine->getManager();
        $entityManager->persist($client);
        $entityManager->flush();

        $client = $normalizer->normalize($client, null, ['groups' => Client::GROUP__ADD]);

        return ApiJsonResponse::ok($client);
    }

    /**
     * View client by ID
     * 
     * @Route("/api/client/{id}", name="client_view", methods={"GET"})
     * 
     * @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="The unique identifier of the client",
     *     example="1ec88cf6-c535-6950-86db-ddf1c337345e",
     *     required=true,
     *     @OA\Schema(type="string")
     * )
     * 
     * @OA\Response(
     *     response=Response::HTTP_OK,
     *     description="Returns client by ID",
     *     @OA\JsonContent(type="object",
     *         @OA\Property(property="status", format="bool", default=true),
     *         @OA\Property(property="msg", format="string", default="ok"),
     *         @OA\Property(property="content", ref=@Model(type=Client::class, groups={Client::GROUP__VIEW}))
     *     )
     * )
     * 
     * @OA\Response(response=Response::HTTP_BAD_REQUEST, description=self::ERROR__WRONG_CLIENT_ID_FORMAT, 
     *     @OA\JsonContent(type="object",
     *         @OA\Property(property=ApiJsonResponse::FIELD__STATUS, format="bool", default=false),
     *         @OA\Property(property=ApiJsonResponse::FIELD__CONTENT, type="object",
     *             @OA\Property(property=ApiJsonResponse::FIELD__ERROR_MSG, type="string", default=self::ERROR__WRONG_CLIENT_ID_FORMAT),
     *             @OA\Property(property=ApiJsonResponse::FIELD__ERROR_CODE, type="int", default=Response::HTTP_BAD_REQUEST),
     *             @OA\Property(property=ApiJsonResponse::FIELD__INVALID_FIELD, type="string", default=null)
     *         )
     *     )
     * )
     * 
     * @OA\Response(response=Response::HTTP_NOT_FOUND, description=self::ERROR__CLIENT_NOT_FOUND, 
     *     @OA\JsonContent(type="object",
     *         @OA\Property(property=ApiJsonResponse::FIELD__STATUS, format="bool", default=false),
     *         @OA\Property(property=ApiJsonResponse::FIELD__CONTENT, type="object",
     *             @OA\Property(property=ApiJsonResponse::FIELD__ERROR_MSG, type="string", default=self::ERROR__CLIENT_NOT_FOUND),
     *             @OA\Property(property=ApiJsonResponse::FIELD__ERROR_CODE, type="int", default=Response::HTTP_NOT_FOUND),
     *             @OA\Property(property=ApiJsonResponse::FIELD__INVALID_FIELD, type="string", default=null)
     *         )
     *     )
     * )
     * 
     * @OA\Tag(name="Public API")
     */
    public function view($id,  NormalizerInterface $normalizer): Response
    {
        if (false === Uuid::isValid($id))
            return ApiJsonResponse::error(self::ERROR__WRONG_CLIENT_ID_FORMAT, Response::HTTP_BAD_REQUEST);

        $client = $this->repository->find($id);

        if (null === $client)
            return ApiJsonResponse::error(self::ERROR__CLIENT_NOT_FOUND, Response::HTTP_NOT_FOUND);

        $client = $normalizer->normalize($client, null, ['groups' => Client::GROUP__VIEW]);

        return ApiJsonResponse::ok($client);
    }

    /**
     * 
     * Update an existing client
     * 
     * @Route("/api/client/{id}", name="client_update", methods={"PATCH"})
     * 
     * @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="The unique identifier of the client",
     *     example="1ec88cf6-c535-6950-86db-ddf1c337345e",
     *     required=true,
     *     @OA\Schema(type="string")
     * )
     * 
     * @OA\RequestBody(
     *     @OA\MediaType(mediaType="application/json",
     *         @OA\Schema(ref=@Model(type=Client::class, groups={Client::GROUP__VIEW}))
     *     )
     * )
     * 
     * @OA\Response(
     *     response=Response::HTTP_OK,
     *     description="Client updated successfully",
     *     @OA\MediaType(mediaType="application/json",
     *         @OA\Schema(ref=@Model(type=Client::class, groups={Client::GROUP__VIEW}))
     *     )
     * )
     * 
     * @OA\Response(response=Response::HTTP_NOT_ACCEPTABLE, description=self::ERROR__CLIENT_VALIDATION, 
     *     @OA\JsonContent(type="object",
     *         @OA\Property(property=ApiJsonResponse::FIELD__STATUS, format="bool", default=false),
     *         @OA\Property(property=ApiJsonResponse::FIELD__CONTENT, type="object",
     *             @OA\Property(property=ApiJsonResponse::FIELD__ERROR_MSG, type="string", default=Client::ERROR__BLANK),
     *             @OA\Property(property=ApiJsonResponse::FIELD__ERROR_CODE, type="int", default=Response::HTTP_NOT_ACCEPTABLE),
     *             @OA\Property(property=ApiJsonResponse::FIELD__INVALID_FIELD, type="string", default="email")
     *         )
     *     )
     * )
     * 
     * @OA\Response(response=Response::HTTP_BAD_REQUEST, description=self::ERROR__WRONG_CLIENT_ID_FORMAT, 
     *     @OA\JsonContent(type="object",
     *         @OA\Property(property=ApiJsonResponse::FIELD__STATUS, format="bool", default=false),
     *         @OA\Property(property=ApiJsonResponse::FIELD__CONTENT, type="object",
     *             @OA\Property(property=ApiJsonResponse::FIELD__ERROR_MSG, type="string", default=self::ERROR__WRONG_CLIENT_ID_FORMAT),
     *             @OA\Property(property=ApiJsonResponse::FIELD__ERROR_CODE, type="int", default=Response::HTTP_BAD_REQUEST),
     *             @OA\Property(property=ApiJsonResponse::FIELD__INVALID_FIELD, type="string", default=null)
     *         )
     *     )
     * )
     * 
     * @OA\Response(response=Response::HTTP_NOT_FOUND, description=self::ERROR__CLIENT_NOT_FOUND, 
     *     @OA\JsonContent(type="object",
     *         @OA\Property(property=ApiJsonResponse::FIELD__STATUS, format="bool", default=false),
     *         @OA\Property(property=ApiJsonResponse::FIELD__CONTENT, type="object",
     *             @OA\Property(property=ApiJsonResponse::FIELD__ERROR_MSG, type="string", default=self::ERROR__CLIENT_NOT_FOUND),
     *             @OA\Property(property=ApiJsonResponse::FIELD__ERROR_CODE, type="int", default=Response::HTTP_NOT_FOUND),
     *             @OA\Property(property=ApiJsonResponse::FIELD__INVALID_FIELD, type="string", default=null)
     *         )
     *     )
     * )
     * 
     * @OA\Tag(name="Public API")
     * 
     * @ParamConverter("id", class="App\Entity\Client", options={"id": "id"})
     */
    public function updateAction(Client $client, Request $request, NormalizerInterface $normalizer, ManagerRegistry $doctrine, ValidatorInterface $validator): Response
    {
        // TODO use DTO for inputs
        // split validation for DTO and for Entity
        // first will be DTO and only then Entity (because Entity validation triggers Database)

        // TODO handle all exceptions and throw them as json response

        $client = $this->requestHandler->populateEntity($request, $client, Client::GROUP__VIEW);

        $errors = $validator->validate($client, null, [Client::GROUP__UPDATE]);

        if (count($errors) > 0) {
            return ApiJsonResponse::error($errors[0]->getMessage(), Response::HTTP_NOT_ACCEPTABLE, $errors[0]->getPropertyPath());
        }
        
        // TODO use services
        // https://www.thinktocode.com/2019/01/24/doctrine-repositories-should-be-collections-without-flush/

        $entityManager = $doctrine->getManager();
        $entityManager->persist($client);

        $updated_client = $normalizer->normalize($client, null, ['groups' => Client::GROUP__VIEW]);

        // TODO use DTO for output ?

        return ApiJsonResponse::ok($updated_client);
    }

    /**
     * 
     * Delete an existing client
     * 
     * @Route("/api/client/{id}", name="client_delete", methods={"DELETE"})
     * 
     * @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="The unique identifier of the client",
     *     example="1ec88cf6-c535-6950-86db-ddf1c337345e",
     *     required=true,
     *     @OA\Schema(type="string")
     * )
     * 
     * @OA\Response(
     *     response=Response::HTTP_OK,
     *     description="Client deleted successfully",
     *     @OA\JsonContent(type="object",
     *         @OA\Property(property=ApiJsonResponse::FIELD__STATUS, format="bool", default=true),
     *         @OA\Property(property=ApiJsonResponse::FIELD__CONTENT, type="object", default=null)
     *     )
     * )
     * 
     * @OA\Response(response=Response::HTTP_BAD_REQUEST, description=self::ERROR__WRONG_CLIENT_ID_FORMAT, 
     *     @OA\JsonContent(type="object",
     *         @OA\Property(property=ApiJsonResponse::FIELD__STATUS, format="bool", default=false),
     *         @OA\Property(property=ApiJsonResponse::FIELD__CONTENT, type="object",
     *             @OA\Property(property=ApiJsonResponse::FIELD__ERROR_MSG, type="string", default=self::ERROR__WRONG_CLIENT_ID_FORMAT),
     *             @OA\Property(property=ApiJsonResponse::FIELD__ERROR_CODE, type="int", default=Response::HTTP_BAD_REQUEST),
     *             @OA\Property(property=ApiJsonResponse::FIELD__INVALID_FIELD, type="string", default=null)
     *         )
     *     )
     * )
     * 
     * @OA\Response(response=Response::HTTP_NOT_FOUND, description=self::ERROR__CLIENT_NOT_FOUND, 
     *     @OA\JsonContent(type="object",
     *         @OA\Property(property=ApiJsonResponse::FIELD__STATUS, format="bool", default=false),
     *         @OA\Property(property=ApiJsonResponse::FIELD__CONTENT, type="object",
     *             @OA\Property(property=ApiJsonResponse::FIELD__ERROR_MSG, type="string", default=self::ERROR__CLIENT_NOT_FOUND),
     *             @OA\Property(property=ApiJsonResponse::FIELD__ERROR_CODE, type="int", default=Response::HTTP_NOT_FOUND),
     *             @OA\Property(property=ApiJsonResponse::FIELD__INVALID_FIELD, type="string", default=null)
     *         )
     *     )
     * )
     * 
     * @OA\Tag(name="Public API")
     */
    public function delete($id, ManagerRegistry $doctrine): Response
    {
        if (false === Uuid::isValid($id))
            return ApiJsonResponse::error(self::ERROR__WRONG_CLIENT_ID_FORMAT, Response::HTTP_BAD_REQUEST);

        $existingClient = $this->repository->find($id);

        if (null === $existingClient)
            return ApiJsonResponse::error(self::ERROR__CLIENT_NOT_FOUND, Response::HTTP_NOT_FOUND);

        $entityManager = $doctrine->getManager();
        $entityManager->remove($existingClient);
        $entityManager->flush();

        return ApiJsonResponse::ok(null);
    }
}
