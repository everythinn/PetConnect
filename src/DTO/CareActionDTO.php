<?php

namespace App\DTO;

class CareActionDTO
{
    public function __construct(
        public readonly string $action = '',
        public readonly ?int $itemId = null,
    ) {
    }
}
