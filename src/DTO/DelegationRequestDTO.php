<?php

namespace App\DTO;

class DelegationRequestDTO
{
    public function __construct(
        public readonly int $petId = 0,
        public readonly int $caretakerId = 0,
        public readonly string $startDate = '',
        public readonly string $endDate = '',
    ) {
    }
}
