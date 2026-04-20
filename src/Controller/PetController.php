<?php

namespace App\Controller;

use App\DTO\AdoptPetDTO;
use App\DTO\PetResponseDTO;
use App\Enum\SpeciesEnum;
use App\Repository\PetRepository;
use App\Security\Voter\PetVoter;
use App\Service\PetService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Doctrine\Persistence\ManagerRegistry;

#[Route('/api/pets')]
class PetController extends AbstractController
{
    public function __construct(
        private readonly PetService $petService,
        private readonly PetRepository $petRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('', methods: ['POST'])]
    public function adoptPet(Request $request, #[CurrentUser] $user): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            $name = $data['name'] ?? '';
            $speciesStr = $data['species'] ?? '';

            if (empty($name) || empty($speciesStr)) {
                return $this->json(['error' => 'Name and species are required'], Response::HTTP_BAD_REQUEST);
            }

            try {
                $species = SpeciesEnum::from(strtolower($speciesStr));
            } catch (\ValueError) {
                return $this->json(['error' => 'Invalid species'], Response::HTTP_BAD_REQUEST);
            }

            $pet = $this->petService->adoptPet($user, $name, $species);

            $response = new PetResponseDTO(
                id: $pet->getId(),
                name: $pet->getName(),
                species: $pet->getSpecies()->value,
                level: $pet->getLevel(),
                xp: $pet->getXp(),
                xpToNextLevel: $pet->getXpToNextLevel(),
                hunger: $pet->getHunger(),
                happiness: $pet->getHappiness(),
                health: $pet->getHealth(),
                energy: $pet->getEnergy(),
                isAlive: $pet->isAlive(),
                bornAt: $pet->getBornAt()->format('Y-m-d H:i:s'),
                lastInteractedAt: $pet->getLastInteractedAt()?->format('Y-m-d H:i:s'),
            );

            return $this->json($response, Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('', methods: ['GET'])]
    public function getPets(#[CurrentUser] $user): JsonResponse
    {
        $pets = $this->petRepository->findByOwner($user);

        $response = array_map(function ($pet) {
            return new PetResponseDTO(
                id: $pet->getId(),
                name: $pet->getName(),
                species: $pet->getSpecies()->value,
                level: $pet->getLevel(),
                xp: $pet->getXp(),
                xpToNextLevel: $pet->getXpToNextLevel(),
                hunger: $pet->getHunger(),
                happiness: $pet->getHappiness(),
                health: $pet->getHealth(),
                energy: $pet->getEnergy(),
                isAlive: $pet->isAlive(),
                bornAt: $pet->getBornAt()->format('Y-m-d H:i:s'),
                lastInteractedAt: $pet->getLastInteractedAt()?->format('Y-m-d H:i:s'),
            );
        }, $pets);

        return $this->json($response);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function getPet(int $id, #[CurrentUser] $user): JsonResponse
    {
        $pet = $this->petRepository->find($id);

        if (!$pet) {
            return $this->json(['error' => 'Pet not found'], Response::HTTP_NOT_FOUND);
        }

        // Check if user can view this pet
        $this->denyAccessUnlessGranted(PetVoter::VIEW, $pet);

        $response = new PetResponseDTO(
            id: $pet->getId(),
            name: $pet->getName(),
            species: $pet->getSpecies()->value,
            level: $pet->getLevel(),
            xp: $pet->getXp(),
            xpToNextLevel: $pet->getXpToNextLevel(),
            hunger: $pet->getHunger(),
            happiness: $pet->getHappiness(),
            health: $pet->getHealth(),
            energy: $pet->getEnergy(),
            isAlive: $pet->isAlive(),
            bornAt: $pet->getBornAt()->format('Y-m-d H:i:s'),
            lastInteractedAt: $pet->getLastInteractedAt()?->format('Y-m-d H:i:s'),
        );

        return $this->json($response);
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function deletePet(int $id, #[CurrentUser] $user): JsonResponse
    {
        $pet = $this->petRepository->find($id);

        if (!$pet) {
            return $this->json(['error' => 'Pet not found'], Response::HTTP_NOT_FOUND);
        }

        // Check if user can delete this pet (owner only)
        $this->denyAccessUnlessGranted(PetVoter::DELETE, $pet);

        $this->petService->deletePet($pet);

        return $this->json(['message' => 'Pet deleted successfully']);
    }
}
