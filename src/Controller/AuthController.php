<?php

namespace App\Controller;

use App\DTO\AuthResponseDTO;
use App\DTO\LoginDTO;
use App\DTO\RegisterDTO;
use App\Entity\Inventory;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/auth')]
class AuthController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly JWTTokenManagerInterface $jwtManager,
        private readonly UserRepository $userRepository,
    ) {
    }

    #[Route('/register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            $registerDTO = new RegisterDTO(
                email: $data['email'] ?? '',
                username: $data['username'] ?? '',
                password: $data['password'] ?? '',
                confirmPassword: $data['confirmPassword'] ?? '',
            );

            // Validate input
            if (empty($registerDTO->email) || empty($registerDTO->username) || empty($registerDTO->password)) {
                return $this->json(['error' => 'Email, username, and password are required'], Response::HTTP_BAD_REQUEST);
            }

            if ($registerDTO->password !== $registerDTO->confirmPassword) {
                return $this->json(['error' => 'Passwords do not match'], Response::HTTP_BAD_REQUEST);
            }

            if (strlen($registerDTO->password) < 6) {
                return $this->json(['error' => 'Password must be at least 6 characters'], Response::HTTP_BAD_REQUEST);
            }

            // Check if email already exists
            if ($this->userRepository->findByEmail($registerDTO->email)) {
                return $this->json(['error' => 'Email already in use'], Response::HTTP_CONFLICT);
            }

            // Create new user
            $user = new User();
            $user->setEmail($registerDTO->email);
            $user->setUsername($registerDTO->username);
            $hashedPassword = $this->passwordHasher->hashPassword($user, $registerDTO->password);
            $user->setPassword($hashedPassword);
            $user->setRoles(['ROLE_USER']);

            // Create inventory for user
            $inventory = new Inventory();
            $inventory->setUser($user);
            $inventory->setItems([]);

            $this->entityManager->persist($user);
            $this->entityManager->persist($inventory);
            $this->entityManager->flush();

            // Generate JWT token
            $token = $this->jwtManager->create($user);

            $response = new AuthResponseDTO(
                token: $token,
                userId: $user->getId(),
                email: $user->getEmail(),
                username: $user->getUsername(),
            );

            return $this->json($response, Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->json(['error' => 'An error occurred during registration'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            $email = $data['email'] ?? '';
            $password = $data['password'] ?? '';

            if (empty($email) || empty($password)) {
                return $this->json(['error' => 'Email and password are required'], Response::HTTP_BAD_REQUEST);
            }

            $user = $this->userRepository->findByEmail($email);

            if (!$user) {
                return $this->json(['error' => 'Invalid credentials'], Response::HTTP_UNAUTHORIZED);
            }

            if (!$this->passwordHasher->isPasswordValid($user, $password)) {
                return $this->json(['error' => 'Invalid credentials'], Response::HTTP_UNAUTHORIZED);
            }

            // Generate JWT token
            $token = $this->jwtManager->create($user);

            $response = new AuthResponseDTO(
                token: $token,
                userId: $user->getId(),
                email: $user->getEmail(),
                username: $user->getUsername(),
            );

            return $this->json($response);
        } catch (\Exception $e) {
            return $this->json(['error' => 'An error occurred during login'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
