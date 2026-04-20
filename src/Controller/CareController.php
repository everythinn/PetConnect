<?php

namespace App\Controller;

use App\Repository\PetRepository;
use App\Security\Voter\PetVoter;
use App\Service\PetService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/pets')]
class CareController extends AbstractController
{
    public function __construct(
        private readonly PetService $petService,
        private readonly PetRepository $petRepository,
    ) {
    }

    #[Route('/{id}/feed', methods: ['POST'])]
    public function feedPet(int $id, #[CurrentUser] $user): JsonResponse
    {
        try {
            $pet = $this->petRepository->find($id);

            if (!$pet) {
                return $this->json(['error' => 'Pet not found'], Response::HTTP_NOT_FOUND);
            }

            // Check if user can feed this pet (owner or active caretaker)
            $this->denyAccessUnlessGranted(PetVoter::FEED, $pet);

            $this->petService->feedPet($pet, $user);

            return $this->json(['message' => 'Pet fed successfully']);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/{id}/play', methods: ['POST'])]
    public function playWithPet(int $id, #[CurrentUser] $user): JsonResponse
    {
        try {
            $pet = $this->petRepository->find($id);

            if (!$pet) {
                return $this->json(['error' => 'Pet not found'], Response::HTTP_NOT_FOUND);
            }

            // Check if user can play with this pet (owner or active caretaker)
            $this->denyAccessUnlessGranted(PetVoter::PLAY, $pet);

            $this->petService->playWithPet($pet, $user);

            return $this->json(['message' => 'Played with pet successfully']);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/{id}/heal', methods: ['POST'])]
    public function healPet(int $id, #[CurrentUser] $user): JsonResponse
    {
        try {
            $pet = $this->petRepository->find($id);

            if (!$pet) {
                return $this->json(['error' => 'Pet not found'], Response::HTTP_NOT_FOUND);
            }

            // Check if user can heal this pet (owner or active caretaker)
            $this->denyAccessUnlessGranted(PetVoter::HEAL, $pet);

            $this->petService->healPet($pet, $user);

            return $this->json(['message' => 'Pet healed successfully']);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/{id}/sleep', methods: ['POST'])]
    public function sleepPet(int $id, #[CurrentUser] $user): JsonResponse
    {
        try {
            $pet = $this->petRepository->find($id);

            if (!$pet) {
                return $this->json(['error' => 'Pet not found'], Response::HTTP_NOT_FOUND);
            }

            // Check if user can make pet sleep (owner or active caretaker)
            $this->denyAccessUnlessGranted(PetVoter::SLEEP, $pet);

            $this->petService->sleepPet($pet, $user);

            return $this->json(['message' => 'Pet is now sleeping']);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/{id}/bathe', methods: ['POST'])]
    public function bathePet(int $id, #[CurrentUser] $user): JsonResponse
    {
        try {
            $pet = $this->petRepository->find($id);

            if (!$pet) {
                return $this->json(['error' => 'Pet not found'], Response::HTTP_NOT_FOUND);
            }

            // Check if user can bathe this pet (owner or active caretaker)
            $this->denyAccessUnlessGranted(PetVoter::BATHE, $pet);

            $this->petService->bathePet($pet, $user);

            return $this->json(['message' => 'Pet bathed successfully']);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }
}
