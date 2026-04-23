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

    public function getToken(): string { return $this->token; }
    public function getUserId(): int { return $this->userId; }
    public function getEmail(): string { return $this->email; }
    public function getUsername(): string { return $this->username; }
}
