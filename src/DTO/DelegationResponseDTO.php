<?php

namespace App\DTO;

class DelegationResponseDTO
{
    public function __construct(
        public readonly int $id,
        public readonly int $petId,
        public readonly int $ownerId,
        public readonly int $caretakerId,
        public readonly string $startDate,
        public readonly string $endDate,
        public readonly string $status,
    ) {
    }
}
