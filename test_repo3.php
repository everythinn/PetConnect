<?php
use App\Kernel;
use App\Entity\Item;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Dotenv\Dotenv;

require __DIR__.'/vendor/autoload.php';

(new Dotenv())->bootEnv(__DIR__.'/.env');

$kernel = new Kernel('dev', true);
$kernel->boot();

/** @var EntityManagerInterface $em */
$em = $kernel->getContainer()->get('doctrine')->getManager();
$items = $em->getRepository(Item::class)->findAll();

echo "Loaded " . count($items) . " items.\n";
foreach ($items as $item) {
    echo $item->getName() . " -> " . $item->getType()->value . "\n";
}
