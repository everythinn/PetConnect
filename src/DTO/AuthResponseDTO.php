<?php

namespace App\DTO;

class AuthResponseDTO
{
    public function __construct(
        public readonly string $token,
        public readonly int $userId,
        public readonly string $email,
        public readonly string $username,
    ) {
    }
}
