<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase as BaseWebTestCase;

abstract class WebTestCase extends BaseWebTestCase
{
    protected function createAuthenticatedClient(string $username = 'testapi', string $password = 'Test1234!')
    {
        $client = static::createClient();
        
        
        $client->request('POST', '/api/auth/login', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'username' => $username,
            'password' => $password,
        ]));

        $response = json_decode($client->getResponse()->getContent(), true);
        
        if (isset($response['token'])) {
            $client->setServerParameter('HTTP_AUTHORIZATION', 'Bearer ' . $response['token']);
        }

        return $client;
    }
}
