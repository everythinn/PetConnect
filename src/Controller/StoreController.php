<?php

namespace App\Controller;

use App\Repository\ItemRepository;
use App\Service\InventoryService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/store')]
#[IsGranted('ROLE_USER')]
class StoreController extends AbstractController
{
    public function __construct(
        private readonly ItemRepository $itemRepository,
        private readonly InventoryService $inventoryService,
    ) {
    }

    #[Route('', methods: ['GET'])]
    public function listItems(): JsonResponse
    {
        $items = $this->itemRepository->findAll();

        $itemsData = array_map(function ($item) {
            return [
                'id' => $item->getId(),
                'name' => $item->getName(),
                'type' => $item->getType()->value,
                'effectValue' => $item->getEffectValue(),
                'description' => $item->getDescription(),
            ];
        }, $items);

        return $this->json([
            'items' => $itemsData,
        ]);
    }

    #[Route('/buy/{itemId}', methods: ['POST'])]
    public function buyItem(int $itemId, #[CurrentUser] $user): JsonResponse
    {
        $item = $this->itemRepository->find($itemId);

        if (!$item) {
            return $this->json(['error' => 'Item not found'], Response::HTTP_NOT_FOUND);
        }

        try {
            $this->inventoryService->addItem($user, $itemId, 1);
            
            return $this->json([
                'message' => 'Item purchased successfully',
                'itemId' => $itemId,
                'itemName' => $item->getName(),
                'quantity' => $this->inventoryService->getItemQuantity($user, $itemId),
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Failed to purchase item'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
