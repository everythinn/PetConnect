<?php

namespace App\Security;

use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTEncodeFailureException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class JwtAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private readonly JWTEncoderInterface $jwtEncoder,
    ) {
    }

    public function supports(Request $request): bool
    {
        $authHeader = $request->headers->get('Authorization');
        
        return $authHeader && str_starts_with($authHeader, 'Bearer ');
    }

    public function authenticate(Request $request): Passport
    {
        $authHeader = $request->headers->get('Authorization');
        $token = substr($authHeader, 7);

        try {
            $payload = $this->jwtEncoder->decode($token);
        } catch (JWTEncodeFailureException|\Exception) {
            throw new AuthenticationException('Invalid JWT token');
        }

        $email = $payload['email'] ?? null;

        if (!$email) {
            throw new AuthenticationException('Invalid token payload');
        }

        return new SelfValidatingPassport(
            new UserBadge($email)
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new Response('Invalid JWT token', Response::HTTP_UNAUTHORIZED);
    }
}
