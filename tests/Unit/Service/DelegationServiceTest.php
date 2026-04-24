<?php

namespace App\Tests\Unit\Service;

use App\Entity\Delegation;
use App\Entity\Pet;
use App\Entity\User;
use App\Enum\DelegationStatusEnum;
use App\Enum\SpeciesEnum;
use App\Repository\DelegationRepository;
use App\Service\DelegationService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class DelegationServiceTest extends TestCase
{
    private DelegationService $delegationService;
    private DelegationRepository&MockObject $delegationRepository;
    private EntityManagerInterface&MockObject $entityManager;
    private User $owner;
    private User $caretaker;
    private Pet $pet;

    protected function setUp(): void
    {
        $this->delegationRepository = $this->createMock(DelegationRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $this->delegationService = new DelegationService(
            $this->delegationRepository,
            $this->entityManager,
        );

        
        $this->owner = new User();
        $this->owner->setUsername('owner');
        $this->owner->setEmail('owner@example.com');

        $this->caretaker = new User();
        $this->caretaker->setUsername('caretaker');
        $this->caretaker->setEmail('caretaker@example.com');

        $this->pet = new Pet();
        $this->pet->setName('TestPet');
        $this->pet->setSpecies(SpeciesEnum::DOG);
    }

    public function testCreateDelegation(): void
    {
        
        $startDate = (new \DateTimeImmutable())->add(new \DateInterval('P1D'));
        $endDate = $startDate->add(new \DateInterval('P7D'));

        
        $delegation = $this->delegationService->createDelegation($this->pet, $this->owner, $this->caretaker, $startDate, $endDate);

        
        $this->assertInstanceOf(Delegation::class, $delegation);
        $this->assertEquals($this->pet, $delegation->getPet());
        $this->assertEquals($this->owner, $delegation->getOwner());
        $this->assertEquals($this->caretaker, $delegation->getCaretaker());
        $this->assertEquals($startDate, $delegation->getStartDate());
        $this->assertEquals($endDate, $delegation->getEndDate());
        $this->assertEquals(DelegationStatusEnum::PENDING, $delegation->getStatus());
    }

    public function testCreateDelegationInvalidDates(): void
    {
        
        $startDate = new \DateTimeImmutable();
        $endDate = $startDate->sub(new \DateInterval('P1D'));

        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('End date must be after start date');

        
        $this->delegationService->createDelegation($this->pet, $this->owner, $this->caretaker, $startDate, $endDate);
    }

    public function testCreateDelegationTodayBecomesActive(): void
    {
        
        $today = (new \DateTimeImmutable())->setTime(0, 0, 0);
        $tomorrow = (new \DateTimeImmutable())->add(new \DateInterval('P1D'))->setTime(23, 59, 59);

        
        $delegation = $this->delegationService->createDelegation($this->pet, $this->owner, $this->caretaker, $today, $tomorrow);

        
        $this->assertEquals(DelegationStatusEnum::ACTIVE, $delegation->getStatus());
    }

    public function testIsDelegationActive(): void
    {
        
        $today = (new \DateTimeImmutable())->setTime(0, 0, 0);
        $tomorrow = (new \DateTimeImmutable())->add(new \DateInterval('P1D'))->setTime(23, 59, 59);

        $delegation = $this->delegationService->createDelegation($this->pet, $this->owner, $this->caretaker, $today, $tomorrow);

        
        $isActive = $this->delegationService->isDelegationActive($delegation);

        
        $this->assertTrue($isActive);
    }

    public function testRevokeDelegation(): void
    {
        
        $startDate = (new \DateTimeImmutable())->setTime(0, 0, 0);
        $endDate = (new \DateTimeImmutable())->add(new \DateInterval('P7D'))->setTime(23, 59, 59);

        $delegation = $this->delegationService->createDelegation($this->pet, $this->owner, $this->caretaker, $startDate, $endDate);

        
        $this->delegationService->revokeDelegation($delegation);

        
        $this->assertEquals(DelegationStatusEnum::REVOKED, $delegation->getStatus());
    }

    public function testRevokeAlreadyRevokedDelegation(): void
    {
        
        $startDate = (new \DateTimeImmutable())->setTime(0, 0, 0);
        $endDate = (new \DateTimeImmutable())->add(new \DateInterval('P7D'))->setTime(23, 59, 59);

        $delegation = $this->delegationService->createDelegation($this->pet, $this->owner, $this->caretaker, $startDate, $endDate);
        $delegation->setStatus(DelegationStatusEnum::REVOKED);

        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Delegation is already revoked');

        
        $this->delegationService->revokeDelegation($delegation);
    }

    public function testRevokeExpiredDelegation(): void
    {
        
        $yesterday = (new \DateTimeImmutable())->sub(new \DateInterval('P1D'))->setTime(23, 59, 59);
        $twoDaysAgo = $yesterday->sub(new \DateInterval('P1D'))->setTime(0, 0, 0);

        $delegation = new Delegation();
        $delegation->setPet($this->pet);
        $delegation->setOwner($this->owner);
        $delegation->setCaretaker($this->caretaker);
        $delegation->setStartDate($twoDaysAgo);
        $delegation->setEndDate($yesterday);
        $delegation->setStatus(DelegationStatusEnum::EXPIRED);

        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cannot revoke an already expired delegation');

        
        $this->delegationService->revokeDelegation($delegation);
    }

    public function testGetActiveDelegationForCaretaker(): void
    {
        
        $today = (new \DateTimeImmutable())->setTime(0, 0, 0);
        $tomorrow = (new \DateTimeImmutable())->add(new \DateInterval('P1D'))->setTime(23, 59, 59);

        $delegation = $this->delegationService->createDelegation($this->pet, $this->owner, $this->caretaker, $today, $tomorrow);

        $this->delegationRepository->expects($this->once())
            ->method('findBy')
            ->with(['pet' => $this->pet, 'caretaker' => $this->caretaker])
            ->willReturn([$delegation]);

        
        $activeDelegation = $this->delegationService->getActiveDelegationForCaretaker($this->pet, $this->caretaker);

        
        $this->assertNotNull($activeDelegation);
        $this->assertEquals($delegation->getId(), $activeDelegation->getId());
    }

    public function testGetActiveDelegationForCaretakerNone(): void
    {
        
        $this->delegationRepository->expects($this->once())
            ->method('findBy')
            ->with(['pet' => $this->pet, 'caretaker' => $this->caretaker])
            ->willReturn([]);

        
        $activeDelegation = $this->delegationService->getActiveDelegationForCaretaker($this->pet, $this->caretaker);

        
        $this->assertNull($activeDelegation);
    }
}
