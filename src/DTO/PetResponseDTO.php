<?php

namespace App\DTO;

class PetResponseDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $species,
        public readonly int $level,
        public readonly int $xp,
        public readonly int $xpToNextLevel,
        public readonly int $hunger,
        public readonly int $happiness,
        public readonly int $health,
        public readonly int $energy,
        public readonly bool $isAlive,
        public readonly string $bornAt,
        public readonly ?string $lastInteractedAt = null,
    ) {
    }
}
