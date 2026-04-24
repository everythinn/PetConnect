<?php

namespace App\Controller;

use App\Enum\ItemTypeEnum;
use App\Repository\PetRepository;
use App\Repository\ItemRepository;
use App\Repository\InventoryRepository;
use App\Security\Voter\PetVoter;
use App\Service\PetService;
use Doctrine\ORM\EntityManagerInterface;
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
        private readonly ItemRepository $itemRepository,
        private readonly InventoryRepository $inventoryRepository,
        private readonly EntityManagerInterface $entityManager,
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

            $this->denyAccessUnlessGranted(PetVoter::BATHE, $pet);

            $this->petService->bathePet($pet, $user);

            return $this->json(['message' => 'Pet bathed successfully']);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/{id}/inventory-items', methods: ['GET'])]
    public function getInventoryItems(int $id, Request $request, #[CurrentUser] $user): JsonResponse
    {
        try {
            $pet = $this->petRepository->find($id);

            if (!$pet) {
                return $this->json(['error' => 'Pet not found'], Response::HTTP_NOT_FOUND);
            }

            $this->denyAccessUnlessGranted(PetVoter::VIEW, $pet);

            $typeStr = $request->query->get('type');
            if (!$typeStr) {
                return $this->json(['error' => 'Item type is required'], Response::HTTP_BAD_REQUEST);
            }

            try {
                $itemType = ItemTypeEnum::from(strtolower($typeStr));
            } catch (\ValueError) {
                return $this->json(['error' => 'Invalid item type'], Response::HTTP_BAD_REQUEST);
            }

            $inventory = $this->inventoryRepository->findByUser($user);
            if (!$inventory) {
                return $this->json([]);
            }

            $userItemIds = array_keys($inventory->getItems());
            $items = [];

            if (!empty($userItemIds)) {
                $items = $this->itemRepository->findBy([
                    'id' => $userItemIds,
                    'type' => $itemType,
                ]);
            }

            $response = array_map(function ($item) use ($inventory) {
                return [
                    'id' => $item->getId(),
                    'name' => $item->getName(),
                    'type' => $item->getType()->value,
                    'effectValue' => $item->getEffectValue(),
                    'quantity' => $inventory->getItemQuantity($item->getId()),
                ];
            }, $items);

            return $this->json($response);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/{id}/use-item/{itemId}', methods: ['POST'])]
    public function useItemOnPet(int $id, int $itemId, #[CurrentUser] $user): JsonResponse
    {
        try {
            $pet = $this->petRepository->find($id);

            if (!$pet) {
                return $this->json(['error' => 'Pet not found'], Response::HTTP_NOT_FOUND);
            }

            // Check if user can use item on this pet (owner or active caretaker)
            $this->denyAccessUnlessGranted(PetVoter::USE_ITEM, $pet);

            $item = $this->itemRepository->find($itemId);

            if (!$item) {
                return $this->json(['error' => 'Item not found'], Response::HTTP_NOT_FOUND);
            }

            $inventory = $this->inventoryRepository->findByUser($user);

            if (!$inventory || !$inventory->hasItem($itemId, 1)) {
                return $this->json(['error' => 'Item not in inventory'], Response::HTTP_BAD_REQUEST);
            }

            $this->petService->useItemOnPet($pet, $user, $item);

            $inventory->removeItem($itemId, 1);
            $this->entityManager->flush();

            return $this->json(['message' => 'Item used successfully']);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }
}
