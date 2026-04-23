<?php

namespace App\DTO;

class CareActionResponseDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $actionType,
        public readonly int $statDelta,
        public readonly int $xpEarned,
        public readonly string $performedAt,
    ) {
    }
}
