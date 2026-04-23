<?php
require 'vendor/autoload.php';

$dto = new \App\DTO\PetResponseDTO(
    id: 1, name: 'Test', species: 'dog', level: 1, xp: 0, xpToNextLevel: 100,
    hunger: 50, happiness: 50, health: 100, energy: 100, isAlive: true,
    bornAt: '2023-01-01 00:00:00', ownerId: 42
);

echo json_encode($dto);
