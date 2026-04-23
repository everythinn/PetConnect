<?php
require __DIR__.'/vendor/autoload.php';

use App\DTO\PetResponseDTO;

$dto = new PetResponseDTO(
    id: 1,
    name: 'test',
    species: 'dog',
    level: 1,
    xp: 0,
    xpToNextLevel: 100,
    hunger: 50,
    happiness: 50,
    health: 100,
    energy: 100,
    isAlive: true,
    bornAt: '2023-01-01',
    lastInteractedAt: null,
    age: 0,
    xpProgress: 0,
    careActions: [],
    ownerId: 5
);

echo json_encode($dto);
