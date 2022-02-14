<?php

namespace App\Security;

use App\Utils\ApiJsonResponse;
use App\Repository\AgentRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\PassportInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;

class ApiAuthenticator extends AbstractAuthenticator
{
    const ERROR__WRONG_TOKEN = 'Wrong API token provided';

    private $agentRepository;

    public function __construct(AgentRepository $agentRepository)
    {
        $this->agentRepository = $agentRepository;
    }

    /**
     * Called on every request to decide if this authenticator should be
     * used for the request. Returning `false` will cause this authenticator
     * to be skipped.
     */
    public function supports(Request $request): ?bool
    {
        return true;
    }

    public function authenticate(Request $request): PassportInterface
    {
        $apiToken = $request->headers->get('Authorization', null);

        if (null === $apiToken || false === str_contains($apiToken, 'Bearer ')) {
            throw new CustomUserMessageAuthenticationException(self::ERROR__WRONG_TOKEN);
        }

        $apiToken = str_replace('Bearer ', '', $apiToken);

        return new SelfValidatingPassport(
            new UserBadge($apiToken, function ($userIdentifier) {
                return $this->agentRepository->findOneBy(['apiToken' => $userIdentifier]);
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // on success, let the request continue
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return ApiJsonResponse::error(self::ERROR__WRONG_TOKEN, Response::HTTP_UNAUTHORIZED);
    }
}
