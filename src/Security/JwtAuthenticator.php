<?php

namespace App\Security;

use App\Repository\UserRepository;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTEncodeFailureException;
use Psr\Log\LoggerInterface;
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
        private readonly UserRepository $userRepository,
        private readonly LoggerInterface $logger,
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
            $this->logger->info('JWT decoded successfully', ['email' => $payload['email'] ?? 'unknown']);
        } catch (JWTEncodeFailureException|\Exception $e) {
            $this->logger->error('JWT decode failed', ['error' => $e->getMessage()]);
            throw new AuthenticationException('Invalid JWT token');
        }

        $email = $payload['email'] ?? null;

        if (!$email) {
            $this->logger->error('Email not found in JWT payload');
            throw new AuthenticationException('Invalid token payload');
        }

        $user = $this->userRepository->findByEmail($email);
        
        if (!$user) {
            $this->logger->error('User not found for email', ['email' => $email]);
            throw new AuthenticationException('User not found');
        }

        $this->logger->info('User authenticated successfully', ['email' => $email]);

        return new SelfValidatingPassport(
            new UserBadge($email, fn() => $user)
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $this->logger->error('Authentication failed', ['message' => $exception->getMessage()]);
        return new Response(
            json_encode(['error' => $exception->getMessage()]), 
            Response::HTTP_UNAUTHORIZED,
            ['Content-Type' => 'application/json']
        );
    }
}
