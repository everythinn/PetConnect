<?php

namespace App\DTO;

class AdoptPetDTO
{
    public function __construct(
        public readonly string $name = '',
        public readonly string $species = '',
    ) {
    }
}
