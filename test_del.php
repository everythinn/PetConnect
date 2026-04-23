<?php
use App\Kernel;
use App\Entity\Delegation;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Dotenv\Dotenv;

require __DIR__.'/vendor/autoload.php';

(new Dotenv())->bootEnv(__DIR__.'/.env');

$kernel = new Kernel('dev', true);
$kernel->boot();

/** @var EntityManagerInterface $em */
$em = $kernel->getContainer()->get('doctrine')->getManager();
$delegations = $em->getRepository(Delegation::class)->findAll();

echo "Loaded " . count($delegations) . " delegations.\n";
foreach ($delegations as $d) {
    echo "ID: " . $d->getId() . " Pet: " . $d->getPet()->getId() . " Owner: " . $d->getOwner()->getId() . " Caretaker: " . $d->getCaretaker()->getId() . " Status: " . $d->getStatus()->value . "\n";
}
