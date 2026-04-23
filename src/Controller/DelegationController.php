<?php

namespace App\Controller;

use App\DTO\DelegationRequestDTO;
use App\Enum\DelegationStatusEnum;
use App\Repository\DelegationRepository;
use App\Repository\PetRepository;
use App\Repository\UserRepository;
use App\Security\Voter\DelegationVoter;
use App\Service\DelegationService;
use Doctrine\ORM\EntityManagerInterface;
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
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    private function buildDelegationResponse($delegation): array
    {
        return [
            'id' => $delegation->getId(),
            'petId' => $delegation->getPet()->getId(),
            'petName' => $delegation->getPet()->getName(),
            'ownerId' => $delegation->getOwner()->getId(),
            'ownerName' => $delegation->getOwner()->getUsername(),
            'ownerEmail' => $delegation->getOwner()->getEmail(),
            'caretakerId' => $delegation->getCaretaker()->getId(),
            'caretakerName' => $delegation->getCaretaker()->getUsername(),
            'caretakerEmail' => $delegation->getCaretaker()->getEmail(),
            'startDate' => $delegation->getStartDate()->format('Y-m-d H:i:s'),
            'endDate' => $delegation->getEndDate()->format('Y-m-d H:i:s'),
            'status' => $delegation->getStatus()->value,
        ];
    }

    #[Route('', methods: ['POST'])]
    public function createDelegation(Request $request, #[CurrentUser] $user): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            $petId = $data['petId'] ?? null;
            $caretakerEmailOrUsername = $data['caretakerEmail'] ?? $data['emailOrUsername'] ?? null;
            $caretakerId = $data['caretakerId'] ?? null;
            $startDateStr = $data['startDate'] ?? null;
            $endDateStr = $data['endDate'] ?? null;

            if (!$petId || (!$caretakerId && !$caretakerEmailOrUsername) || !$startDateStr || !$endDateStr) {
                return $this->json(['error' => 'Missing required fields'], Response::HTTP_BAD_REQUEST);
            }

            $pet = $this->petRepository->find($petId);
            if (!$pet || $pet->getOwner()->getId() !== $user->getId()) {
                return $this->json(['error' => 'Pet not found or unauthorized'], Response::HTTP_NOT_FOUND);
            }

            $caretaker = $caretakerId
                ? $this->userRepository->find($caretakerId)
                : $this->userRepository->findByEmailOrUsername($caretakerEmailOrUsername);
            if (!$caretaker) {
                return $this->json(['error' => 'Caretaker not found'], Response::HTTP_NOT_FOUND);
            }

            $startDate = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $startDateStr);
            if (!$startDate) {
                // Try ISO format (from datetime-local input)
                $startDate = \DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $startDateStr);
            }
            
            $endDate = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $endDateStr);
            if (!$endDate) {
                // Try ISO format (from datetime-local input)
                $endDate = \DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $endDateStr);
            }

            if (!$startDate || !$endDate) {
                return $this->json(['error' => 'Invalid date format'], Response::HTTP_BAD_REQUEST);
            }

            $delegation = $this->delegationService->createDelegation($pet, $user, $caretaker, $startDate, $endDate);

            return $this->json($this->buildDelegationResponse($delegation), Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('', methods: ['GET'])]
    public function getDelegations(#[CurrentUser] $user): JsonResponse
    {
        $delegationsAsOwner = $this->delegationRepository->findByOwner($user);
        $delegationsAsCaretaker = $this->delegationRepository->findByCaretaker($user);

        // Update status for all delegations based on current date
        $allUnique = array_unique(array_merge($delegationsAsOwner, $delegationsAsCaretaker), SORT_REGULAR);
        foreach ($allUnique as $delegation) {
            $this->delegationService->updateDelegationStatus($delegation);
        }

        return $this->json([
            'created' => array_map(fn($d) => $this->buildDelegationResponse($d), $delegationsAsOwner),
            'received' => array_map(fn($d) => $this->buildDelegationResponse($d), $delegationsAsCaretaker),
        ]);
    }

    #[Route('/validate-caretaker', methods: ['POST'])]
    public function validateCaretaker(Request $request, #[CurrentUser] $user): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $emailOrUsername = $data['emailOrUsername'] ?? null;

        if (!$emailOrUsername) {
            return $this->json(['valid' => false, 'error' => 'Email or username required'], Response::HTTP_BAD_REQUEST);
        }

        $caretaker = $this->userRepository->findByEmailOrUsername($emailOrUsername);

        if (!$caretaker) {
            return $this->json(['valid' => false]);
        }

        // Cannot delegate to oneself
        if ($caretaker->getId() === $user->getId()) {
            return $this->json(['valid' => false, 'error' => 'Cannot delegate to yourself']);
        }

        return $this->json(['valid' => true, 'username' => $caretaker->getUsername()]);
    }

    #[Route('/{id}/cancel', methods: ['PATCH'])]
    public function cancelDelegation(int $id, #[CurrentUser] $user): JsonResponse
    {
        try {
            $delegation = $this->delegationRepository->find($id);

            if (!$delegation) {
                return $this->json(['error' => 'Delegation not found'], Response::HTTP_NOT_FOUND);
            }

            // Check if user can cancel this delegation (owner only)
            $this->denyAccessUnlessGranted(DelegationVoter::REVOKE, $delegation);

            $this->delegationService->revokeDelegation($delegation);

            $response = $this->buildDelegationResponse($delegation);

            return $this->json($response);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

}
