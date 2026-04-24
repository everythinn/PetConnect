<?php

namespace App\Tests\Functional\Controller;

use App\Entity\Pet;
use App\Entity\User;
use App\Entity\Inventory;
use App\Enum\SpeciesEnum;
use App\Repository\PetRepository;
use App\Repository\UserRepository;
use App\Tests\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class PetControllerTest extends WebTestCase
{
    private PetRepository $petRepository;
    private UserRepository $userRepository;
    private UserPasswordHasherInterface $passwordHasher;
    private User $testUser;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->petRepository = static::getContainer()->get(PetRepository::class);
        $this->userRepository = static::getContainer()->get(UserRepository::class);
        $this->passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        
        $this->testUser = $this->createTestUser();
        $this->token = $this->getTokenForUser($this->testUser->getEmail(), 'TestPassword123');
    }

    private function createTestUser(): User
    {
        $container = static::getContainer();
        $entityManager = $container->get('doctrine.orm.entity_manager');

        $user = new User();
        $user->setEmail('pet_test@example.com');
        $user->setUsername('pettestuser');
        $hashedPassword = $this->passwordHasher->hashPassword($user, 'TestPassword123');
        $user->setPassword($hashedPassword);
        $user->setRoles(['ROLE_USER']);

        
        $inventory = new Inventory();
        $inventory->setUser($user);
        $inventory->setItems([]);

        $entityManager->persist($user);
        $entityManager->persist($inventory);
        $entityManager->flush();

        $user->setInventory($inventory);

        return $user;
    }

    private function getTokenForUser(string $email, string $password): string
    {
        $client = static::createClient();
        $client->request(
            'POST',
            '/api/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => $email, 'password' => $password])
        );

        $response = json_decode($client->getResponse()->getContent(), true);
        return $response['token'] ?? '';
    }

    private function getTestClient(): object
    {
        $client = static::createClient();
        $client->setServerParameter('HTTP_AUTHORIZATION', 'Bearer ' . $this->token);
        return $client;
    }

    public function testAdoptPetSuccess(): void
    {
        
        $client = $this->getTestClient();
        $payload = [
            'name' => 'Fluffy',
            'species' => 'cat',
        ];

        
        $client->request(
            'POST',
            '/api/pets',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $this->token],
            json_encode($payload)
        );

        
        $this->assertResponseStatusCodeSame(201);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('Fluffy', $responseData['name']);
        $this->assertEquals('cat', $responseData['species']);
        $this->assertEquals(1, $responseData['level']);
        $this->assertTrue($responseData['isAlive']);
    }

    public function testAdoptPetInvalidSpecies(): void
    {
        
        $client = $this->getTestClient();
        $payload = [
            'name' => 'Fluffy',
            'species' => 'invalid_species',
        ];

        
        $client->request(
            'POST',
            '/api/pets',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $this->token],
            json_encode($payload)
        );

        
        $this->assertResponseStatusCodeSame(400);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertStringContainsString('Invalid species', $responseData['error']);
    }

    public function testAdoptPetMissingName(): void
    {
        
        $client = $this->getTestClient();
        $payload = [
            'species' => 'dog',
        ];

        
        $client->request(
            'POST',
            '/api/pets',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $this->token],
            json_encode($payload)
        );

        
        $this->assertResponseStatusCodeSame(400);
    }

    public function testAdoptPetUnauthorized(): void
    {
        
        $client = static::createClient();
        $payload = [
            'name' => 'Fluffy',
            'species' => 'cat',
        ];

        
        $client->request(
            'POST',
            '/api/pets',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );

        
        $this->assertResponseStatusCodeSame(401);
    }

    public function testGetPetsSuccess(): void
    {
        
        $pet = new Pet();
        $pet->setOwner($this->testUser);
        $pet->setName('TestPet');
        $pet->setSpecies(SpeciesEnum::DOG);
        $pet->setLevel(1);
        $pet->setXp(0);
        $pet->setXpToNextLevel(100);
        $pet->setHunger(50);
        $pet->setHappiness(50);
        $pet->setHealth(100);
        $pet->setEnergy(100);
        $pet->setIsAlive(true);

        $container = static::getContainer();
        $entityManager = $container->get('doctrine.orm.entity_manager');
        $entityManager->persist($pet);
        $entityManager->flush();

        
        $client = $this->getTestClient();
        $client->request(
            'GET',
            '/api/pets',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $this->token]
        );

        
        $this->assertResponseStatusCodeSame(200);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($responseData);
        $this->assertGreaterThan(0, count($responseData));
        $this->assertEquals('TestPet', $responseData[0]['name']);
    }

    public function testGetPetsEmpty(): void
    {
        
        $newUser = new User();
        $newUser->setEmail('empty_pet_test@example.com');
        $newUser->setUsername('emptypetuser');
        $hashedPassword = $this->passwordHasher->hashPassword($newUser, 'TestPassword123');
        $newUser->setPassword($hashedPassword);
        $newUser->setRoles(['ROLE_USER']);

        $container = static::getContainer();
        $entityManager = $container->get('doctrine.orm.entity_manager');
        $entityManager->persist($newUser);
        $entityManager->flush();

        $token = $this->getTokenForUser('empty_pet_test@example.com', 'TestPassword123');

        
        $client = static::createClient();
        $client->request(
            'GET',
            '/api/pets',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]
        );

        
        $this->assertResponseStatusCodeSame(200);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($responseData);
        $this->assertEquals(0, count($responseData));
    }

    public function testGetPetsUnauthorized(): void
    {
        
        $client = static::createClient();

        
        $client->request('GET', '/api/pets');

        
        $this->assertResponseStatusCodeSame(401);
    }

    public function testGetPetSuccess(): void
    {
        
        $pet = new Pet();
        $pet->setOwner($this->testUser);
        $pet->setName('DetailedPet');
        $pet->setSpecies(SpeciesEnum::CAT);
        $pet->setLevel(2);
        $pet->setXp(50);
        $pet->setXpToNextLevel(150);
        $pet->setHunger(60);
        $pet->setHappiness(70);
        $pet->setHealth(90);
        $pet->setEnergy(80);
        $pet->setIsAlive(true);

        $container = static::getContainer();
        $entityManager = $container->get('doctrine.orm.entity_manager');
        $entityManager->persist($pet);
        $entityManager->flush();

        
        $client = $this->getTestClient();
        $client->request(
            'GET',
            "/api/pets/{$pet->getId()}",
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $this->token]
        );

        
        $this->assertResponseStatusCodeSame(200);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('DetailedPet', $responseData['name']);
        $this->assertEquals('cat', $responseData['species']);
        $this->assertEquals(2, $responseData['level']);
    }

    public function testGetPetNotFound(): void
    {
        
        $invalidPetId = 99999;

        
        $client = $this->getTestClient();
        $client->request(
            'GET',
            "/api/pets/{$invalidPetId}",
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $this->token]
        );

        
        $this->assertResponseStatusCodeSame(404);
    }

    public function testGetPetForbidden(): void
    {
        
        $otherUser = new User();
        $otherUser->setEmail('other_user@example.com');
        $otherUser->setUsername('otheruser');
        $hashedPassword = $this->passwordHasher->hashPassword($otherUser, 'TestPassword123');
        $otherUser->setPassword($hashedPassword);
        $otherUser->setRoles(['ROLE_USER']);

        $container = static::getContainer();
        $entityManager = $container->get('doctrine.orm.entity_manager');
        $entityManager->persist($otherUser);
        $entityManager->flush();

        $otherPet = new Pet();
        $otherPet->setOwner($otherUser);
        $otherPet->setName('OtherUserPet');
        $otherPet->setSpecies(SpeciesEnum::DOG);
        $otherPet->setLevel(1);
        $otherPet->setXp(0);
        $otherPet->setXpToNextLevel(100);
        $otherPet->setHunger(50);
        $otherPet->setHappiness(50);
        $otherPet->setHealth(100);
        $otherPet->setEnergy(100);
        $otherPet->setIsAlive(true);

        $entityManager->persist($otherPet);
        $entityManager->flush();

        
        $client = $this->getTestClient();
        $client->request(
            'GET',
            "/api/pets/{$otherPet->getId()}",
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $this->token]
        );

        
        $this->assertResponseStatusCodeSame(403);
    }

    public function testDeletePetSuccess(): void
    {
        
        $pet = new Pet();
        $pet->setOwner($this->testUser);
        $pet->setName('DeleteMePet');
        $pet->setSpecies(SpeciesEnum::DOG);
        $pet->setLevel(1);
        $pet->setXp(0);
        $pet->setXpToNextLevel(100);
        $pet->setHunger(50);
        $pet->setHappiness(50);
        $pet->setHealth(100);
        $pet->setEnergy(100);
        $pet->setIsAlive(true);

        $container = static::getContainer();
        $entityManager = $container->get('doctrine.orm.entity_manager');
        $entityManager->persist($pet);
        $entityManager->flush();

        $petId = $pet->getId();

        
        $client = $this->getTestClient();
        $client->request(
            'DELETE',
            "/api/pets/{$petId}",
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $this->token]
        );

        
        $this->assertResponseStatusCodeSame(200);

        
        $deletedPet = $this->petRepository->find($petId);
        $this->assertNull($deletedPet);
    }

    public function testDeletePetNotFound(): void
    {
        
        $invalidPetId = 99999;

        
        $client = $this->getTestClient();
        $client->request(
            'DELETE',
            "/api/pets/{$invalidPetId}",
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $this->token]
        );

        
        $this->assertResponseStatusCodeSame(404);
    }

    public function testDeletePetForbidden(): void
    {
        
        $otherUser = new User();
        $otherUser->setEmail('other_delete_user@example.com');
        $otherUser->setUsername('otherdeleteuser');
        $hashedPassword = $this->passwordHasher->hashPassword($otherUser, 'TestPassword123');
        $otherUser->setPassword($hashedPassword);
        $otherUser->setRoles(['ROLE_USER']);

        $container = static::getContainer();
        $entityManager = $container->get('doctrine.orm.entity_manager');
        $entityManager->persist($otherUser);
        $entityManager->flush();

        $otherPet = new Pet();
        $otherPet->setOwner($otherUser);
        $otherPet->setName('OtherUserDeletePet');
        $otherPet->setSpecies(SpeciesEnum::CAT);
        $otherPet->setLevel(1);
        $otherPet->setXp(0);
        $otherPet->setXpToNextLevel(100);
        $otherPet->setHunger(50);
        $otherPet->setHappiness(50);
        $otherPet->setHealth(100);
        $otherPet->setEnergy(100);
        $otherPet->setIsAlive(true);

        $entityManager->persist($otherPet);
        $entityManager->flush();

        
        $client = $this->getTestClient();
        $client->request(
            'DELETE',
            "/api/pets/{$otherPet->getId()}",
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $this->token]
        );

        
        $this->assertResponseStatusCodeSame(403);
    }
}
