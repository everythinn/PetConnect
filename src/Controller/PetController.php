<?php

namespace App\Controller;

use App\DTO\AdoptPetDTO;
use App\DTO\PetResponseDTO;
use App\DTO\CareActionResponseDTO;
use App\Enum\SpeciesEnum;
use App\Enum\DelegationStatusEnum;
use App\Repository\PetRepository;
use App\Repository\CareActionRepository;
use App\Repository\DelegationRepository;
use App\Security\Voter\PetVoter;
use App\Service\PetService;
use App\Service\StatDegradationService;
use App\Service\DelegationService;
use App\Entity\Pet;
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
        private readonly CareActionRepository $careActionRepository,
        private readonly DelegationRepository $delegationRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly StatDegradationService $statDegradationService,
        private readonly DelegationService $delegationService,
    ) {
    }

    private function calculateAge(\DateTimeImmutable $bornAt): int
    {
        $now = new \DateTimeImmutable();
        $interval = $now->diff($bornAt);
        return (int)$interval->days;
    }

    private function calculateXpProgress(Pet $pet): float
    {
        $totalXpNeeded = $pet->getXp() + $pet->getXpToNextLevel();
        if ($totalXpNeeded <= 0) {
            return 0.0;
        }
        return min(100.0, ($pet->getXp() / $totalXpNeeded) * 100);
    }

    private function buildPetResponse(Pet $pet): PetResponseDTO
    {
        $age = $this->calculateAge($pet->getBornAt());
        $xpProgress = $this->calculateXpProgress($pet);
        
        // Get last 3 care actions
        $careActionsRaw = $this->careActionRepository->findByPet($pet);
        $lastThreeCareActions = array_slice($careActionsRaw, 0, 3);
        
        $careActions = array_map(function ($action) {
            return new CareActionResponseDTO(
                id: $action->getId(),
                actionType: $action->getActionType()->value,
                statDelta: $action->getStatDelta(),
                xpEarned: $action->getXpEarned(),
                performedAt: $action->getPerformedAt()->format('Y-m-d H:i:s'),
            );
        }, $lastThreeCareActions);
        
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
            age: $age,
            xpProgress: $xpProgress,
            careActions: $careActions,
            ownerId: $pet->getOwner()->getId()
        );
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
            $response = $this->buildPetResponse($pet);
            return $this->json($response, Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('', methods: ['GET'])]
    public function getPets(#[CurrentUser] $user): JsonResponse
    {
        // Get pets owned by the user
        $ownedPets = $this->petRepository->findByOwner($user);

        // Get delegated pets with active delegations
        $delegatedPets = [];
        $delegations = $this->delegationRepository->findByCaretaker($user);
        
        foreach ($delegations as $delegation) {
            // Only include pets where delegation is active
            if ($this->delegationService->calculateDelegationStatus($delegation) === DelegationStatusEnum::ACTIVE) {
                $delegatedPets[] = $delegation->getPet();
            }
        }

        // Combine both lists, avoiding duplicates
        $allPets = array_merge($ownedPets, $delegatedPets);
        $petIds = array_unique(array_map(fn($pet) => $pet->getId(), $allPets));
        $pets = array_filter($allPets, function($pet, $key) use ($petIds) {
            return array_search($pet->getId(), $petIds) === $key;
        }, ARRAY_FILTER_USE_BOTH);

        // Reset keys to ensure proper JSON array encoding
        $pets = array_values($pets);

        // Degrade stats for each pet
        foreach ($pets as $pet) {
            $this->statDegradationService->degradeStats($pet);
        }

        $response = array_map(fn($pet) => $this->buildPetResponse($pet), $pets);

        return $this->json($response);
    }

    #[Route('/owned', methods: ['GET'])]
    public function getOwnedPets(#[CurrentUser] $user): JsonResponse
    {
        $ownedPets = $this->petRepository->findByOwner($user);
        $response = array_map(fn($pet) => $this->buildPetResponse($pet), $ownedPets);
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

        // Check if pet is alive
        if (!$pet->isAlive()) {
            return $this->json(['error' => 'Pet is dead and cannot be accessed'], Response::HTTP_GONE);
        }

        // Degrade stats
        $this->statDegradationService->degradeStats($pet);

        $response = $this->buildPetResponse($pet);
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
