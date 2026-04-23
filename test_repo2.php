<?php
use App\Kernel;
use App\Entity\Item;
use Doctrine\ORM\EntityManagerInterface;

require dirname(__DIR__).'/project-symfony-petconnect/vendor/autoload.php';
require dirname(__DIR__).'/project-symfony-petconnect/config/bootstrap.php';

$kernel = new Kernel('dev', true);
$kernel->boot();

/** @var EntityManagerInterface $em */
$em = $kernel->getContainer()->get('doctrine')->getManager();
$items = $em->getRepository(Item::class)->findAll();

echo "Loaded " . count($items) . " items.\n";
foreach ($items as $item) {
    echo $item->getName() . " -> " . $item->getEffect()->value . "\n";
}
