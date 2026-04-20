<?php

namespace App\DTO;

class RegisterDTO
{
    public function __construct(
        public readonly string $email = '',
        public readonly string $username = '',
        public readonly string $password = '',
        public readonly string $confirmPassword = '',
    ) {
    }
}
