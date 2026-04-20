<?php

namespace App\Controller;

use App\DTO\DelegationRequestDTO;
use App\DTO\DelegationResponseDTO;
use App\Repository\DelegationRepository;
use App\Repository\PetRepository;
use App\Repository\UserRepository;
use App\Service\DelegationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/delegations')]
class DelegationController extends AbstractController
{
    public function __construct(
        private readonly DelegationService $delegationService,
        private readonly DelegationRepository $delegationRepository,
        private readonly PetRepository $petRepository,
        private readonly UserRepository $userRepository,
    ) {
    }

    #[Route('', methods: ['POST'])]
    public function createDelegation(Request $request, #[CurrentUser] $user): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            $petId = $data['petId'] ?? null;
            $caretakerId = $data['caretakerId'] ?? null;
            $startDateStr = $data['startDate'] ?? null;
            $endDateStr = $data['endDate'] ?? null;

            if (!$petId || !$caretakerId || !$startDateStr || !$endDateStr) {
                return $this->json(['error' => 'Missing required fields'], Response::HTTP_BAD_REQUEST);
            }

            $pet = $this->petRepository->find($petId);
            if (!$pet || $pet->getOwner()->getId() !== $user->getId()) {
                return $this->json(['error' => 'Pet not found or unauthorized'], Response::HTTP_NOT_FOUND);
            }

            $caretaker = $this->userRepository->find($caretakerId);
            if (!$caretaker) {
                return $this->json(['error' => 'Caretaker not found'], Response::HTTP_NOT_FOUND);
            }

            $startDate = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $startDateStr);
            $endDate = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $endDateStr);

            if (!$startDate || !$endDate) {
                return $this->json(['error' => 'Invalid date format'], Response::HTTP_BAD_REQUEST);
            }

            $delegation = $this->delegationService->createDelegation($pet, $user, $caretaker, $startDate, $endDate);

            $response = new DelegationResponseDTO(
                id: $delegation->getId(),
                petId: $delegation->getPet()->getId(),
                ownerId: $delegation->getOwner()->getId(),
                caretakerId: $delegation->getCaretaker()->getId(),
                startDate: $delegation->getStartDate()->format('Y-m-d H:i:s'),
                endDate: $delegation->getEndDate()->format('Y-m-d H:i:s'),
                status: $delegation->getStatus()->value,
            );

            return $this->json($response, Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('', methods: ['GET'])]
    public function getDelegations(#[CurrentUser] $user): JsonResponse
    {
        $delegationsAsOwner = $this->delegationRepository->findByOwner($user);
        $delegationsAsCaretaker = $this->delegationRepository->findByCaretaker($user);

        $allDelegations = array_merge($delegationsAsOwner, $delegationsAsCaretaker);

        $response = array_map(function ($delegation) {
            return new DelegationResponseDTO(
                id: $delegation->getId(),
                petId: $delegation->getPet()->getId(),
                ownerId: $delegation->getOwner()->getId(),
                caretakerId: $delegation->getCaretaker()->getId(),
                startDate: $delegation->getStartDate()->format('Y-m-d H:i:s'),
                endDate: $delegation->getEndDate()->format('Y-m-d H:i:s'),
                status: $delegation->getStatus()->value,
            );
        }, $allDelegations);

        return $this->json($response);
    }

    #[Route('/{id}/accept', methods: ['PATCH'])]
    public function acceptDelegation(int $id, #[CurrentUser] $user): JsonResponse
    {
        try {
            $delegation = $this->delegationRepository->find($id);

            if (!$delegation) {
                return $this->json(['error' => 'Delegation not found'], Response::HTTP_NOT_FOUND);
            }

            if ($delegation->getCaretaker()->getId() !== $user->getId()) {
                return $this->json(['error' => 'Unauthorized'], Response::HTTP_FORBIDDEN);
            }

            $this->delegationService->acceptDelegation($delegation);

            $response = new DelegationResponseDTO(
                id: $delegation->getId(),
                petId: $delegation->getPet()->getId(),
                ownerId: $delegation->getOwner()->getId(),
                caretakerId: $delegation->getCaretaker()->getId(),
                startDate: $delegation->getStartDate()->format('Y-m-d H:i:s'),
                endDate: $delegation->getEndDate()->format('Y-m-d H:i:s'),
                status: $delegation->getStatus()->value,
            );

            return $this->json($response);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/{id}/revoke', methods: ['PATCH'])]
    public function revokeDelegation(int $id, #[CurrentUser] $user): JsonResponse
    {
        try {
            $delegation = $this->delegationRepository->find($id);

            if (!$delegation) {
                return $this->json(['error' => 'Delegation not found'], Response::HTTP_NOT_FOUND);
            }

            if ($delegation->getOwner()->getId() !== $user->getId()) {
                return $this->json(['error' => 'Unauthorized'], Response::HTTP_FORBIDDEN);
            }

            $this->delegationService->revokeDelegation($delegation);

            $response = new DelegationResponseDTO(
                id: $delegation->getId(),
                petId: $delegation->getPet()->getId(),
                ownerId: $delegation->getOwner()->getId(),
                caretakerId: $delegation->getCaretaker()->getId(),
                startDate: $delegation->getStartDate()->format('Y-m-d H:i:s'),
                endDate: $delegation->getEndDate()->format('Y-m-d H:i:s'),
                status: $delegation->getStatus()->value,
            );

            return $this->json($response);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }
}
