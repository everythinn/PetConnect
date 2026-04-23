<?php

namespace App\EventListener;

use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: 'lexik_jwt_authentication.on_jwt_created')]
class JwtEventListener
{
    public function __invoke(JWTCreatedEvent $event): void
    {
        $user = $event->getUser();
        
        $payload = $event->getData();
        $payload['email'] = $user->getEmail();
        
        $event->setData($payload);
    }
}
