<?php
require 'vendor/autoload.php';

use Symfony\Component\HttpFoundation\Request;

$kernel = new App\Kernel('dev', true);
$kernel->boot();

$container = $kernel->getContainer();
$userRepository = $container->get('doctrine')->getRepository(\App\Entity\User::class);
$user = $userRepository->findOneBy([]);

if (!$user) {
    echo "No users found\n";
    exit;
}

$controller = $container->get(\App\Controller\PetController::class);

$response = $controller->getPets($user);
echo $response->getContent();
