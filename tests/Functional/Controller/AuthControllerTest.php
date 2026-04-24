<?php

namespace App\Tests\Functional\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Tests\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AuthControllerTest extends WebTestCase
{
    private UserRepository $userRepository;
    private UserPasswordHasherInterface $passwordHasher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userRepository = static::getContainer()->get(UserRepository::class);
        $this->passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        
        
    }

    public function testRegisterSuccess(): void
    {
        
        $client = static::createClient();
        $payload = [
            'email' => 'newuser@example.com',
            'username' => 'newuser',
            'password' => 'TestPassword123',
            'confirmPassword' => 'TestPassword123',
        ];

        
        $client->request(
            'POST',
            '/api/auth/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );

        
        $this->assertResponseStatusCodeSame(201);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('token', $responseData);
        $this->assertEquals('newuser@example.com', $responseData['email']);
        $this->assertEquals('newuser', $responseData['username']);
    }

    public function testRegisterMissingEmail(): void
    {
        
        $client = static::createClient();
        $payload = [
            'username' => 'newuser',
            'password' => 'TestPassword123',
            'confirmPassword' => 'TestPassword123',
        ];

        
        $client->request(
            'POST',
            '/api/auth/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );

        
        $this->assertResponseStatusCodeSame(400);
    }

    public function testRegisterMissingPassword(): void
    {
        
        $client = static::createClient();
        $payload = [
            'email' => 'newuser@example.com',
            'username' => 'newuser',
            'confirmPassword' => 'TestPassword123',
        ];

        
        $client->request(
            'POST',
            '/api/auth/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );

        
        $this->assertResponseStatusCodeSame(400);
    }

    public function testRegisterPasswordTooShort(): void
    {
        
        $client = static::createClient();
        $payload = [
            'email' => 'newuser@example.com',
            'username' => 'newuser',
            'password' => 'Short',
            'confirmPassword' => 'Short',
        ];

        
        $client->request(
            'POST',
            '/api/auth/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );

        
        $this->assertResponseStatusCodeSame(400);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertStringContainsString('at least 6 characters', $responseData['error']);
    }

    public function testRegisterPasswordMismatch(): void
    {
        
        $client = static::createClient();
        $payload = [
            'email' => 'newuser@example.com',
            'username' => 'newuser',
            'password' => 'TestPassword123',
            'confirmPassword' => 'DifferentPassword',
        ];

        
        $client->request(
            'POST',
            '/api/auth/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );

        
        $this->assertResponseStatusCodeSame(400);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertStringContainsString('do not match', $responseData['error']);
    }

    public function testRegisterEmailAlreadyExists(): void
    {
        
        $container = static::getContainer();
        $entityManager = $container->get('doctrine.orm.entity_manager');
        
        $existingUser = new User();
        $existingUser->setEmail('existing@example.com');
        $existingUser->setUsername('existing');
        $hashedPassword = $this->passwordHasher->hashPassword($existingUser, 'ExistingPassword123');
        $existingUser->setPassword($hashedPassword);
        $existingUser->setRoles(['ROLE_USER']);
        
        $entityManager->persist($existingUser);
        $entityManager->flush();

        
        $client = static::createClient();
        $payload = [
            'email' => 'existing@example.com',
            'username' => 'newuser',
            'password' => 'TestPassword123',
            'confirmPassword' => 'TestPassword123',
        ];

        
        $client->request(
            'POST',
            '/api/auth/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );

        
        $this->assertResponseStatusCodeSame(409);
    }

    public function testLoginSuccess(): void
    {
        
        $container = static::getContainer();
        $entityManager = $container->get('doctrine.orm.entity_manager');
        
        $user = new User();
        $user->setEmail('login@example.com');
        $user->setUsername('loginuser');
        $hashedPassword = $this->passwordHasher->hashPassword($user, 'LoginPassword123');
        $user->setPassword($hashedPassword);
        $user->setRoles(['ROLE_USER']);
        
        $entityManager->persist($user);
        $entityManager->flush();

        
        $client = static::createClient();
        $payload = [
            'email' => 'login@example.com',
            'password' => 'LoginPassword123',
        ];

        
        $client->request(
            'POST',
            '/api/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );

        
        $this->assertResponseStatusCodeSame(200);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('token', $responseData);
        $this->assertEquals('login@example.com', $responseData['email']);
    }

    public function testLoginInvalidEmail(): void
    {
        
        $client = static::createClient();
        $payload = [
            'email' => 'nonexistent@example.com',
            'password' => 'SomePassword123',
        ];

        
        $client->request(
            'POST',
            '/api/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );

        
        $this->assertResponseStatusCodeSame(401);
    }

    public function testLoginInvalidPassword(): void
    {
        
        $container = static::getContainer();
        $entityManager = $container->get('doctrine.orm.entity_manager');
        
        $user = new User();
        $user->setEmail('login2@example.com');
        $user->setUsername('loginuser2');
        $hashedPassword = $this->passwordHasher->hashPassword($user, 'CorrectPassword123');
        $user->setPassword($hashedPassword);
        $user->setRoles(['ROLE_USER']);
        
        $entityManager->persist($user);
        $entityManager->flush();

        
        $client = static::createClient();
        $payload = [
            'email' => 'login2@example.com',
            'password' => 'WrongPassword123',
        ];

        
        $client->request(
            'POST',
            '/api/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );

        
        $this->assertResponseStatusCodeSame(401);
    }

    public function testLoginMissingEmail(): void
    {
        
        $client = static::createClient();
        $payload = [
            'password' => 'SomePassword123',
        ];

        
        $client->request(
            'POST',
            '/api/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );

        
        $this->assertResponseStatusCodeSame(400);
    }

    public function testLoginMissingPassword(): void
    {
        
        $client = static::createClient();
        $payload = [
            'email' => 'user@example.com',
        ];

        
        $client->request(
            'POST',
            '/api/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );

        
        $this->assertResponseStatusCodeSame(400);
    }
}
