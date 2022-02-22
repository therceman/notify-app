<?php

namespace App\RequestHandler;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;

class ApiRequestHandler
{
    /** @var  SerializerInterface */
    private $serializer;

    /**
     * RequestHandler constructor.
     * @param SerializerInterface $serializer
     */
    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    public function populateEntity(Request $request, object $entity, ?string $group = null)
    {
        $deserializeOptions = [
            AbstractNormalizer::OBJECT_TO_POPULATE => $entity
        ];

        if ($group !== null)
            $deserializeOptions[AbstractNormalizer::GROUPS] = $group;

        try {
            $this->serializer->deserialize($request->getContent(), get_class($entity), 'json', $deserializeOptions);
        } catch (NotNormalizableValueException $th) {
            return new BadRequestHttpException('Wrong JSON format provided');
        }

        return $entity;
    }

    public function createEntity(Request $request, string $className, ?string $group = null)
    {
        $deserializeOptions = [];

        if ($group !== null)
            $deserializeOptions[AbstractNormalizer::GROUPS] = $group;

        try {
            $entity = $this->serializer->deserialize($request->getContent(), $className, 'json', $deserializeOptions);
        } catch (NotNormalizableValueException $th) {
            return new BadRequestHttpException('Wrong JSON format provided');
        }

        return $entity;
    }
}
