<?php
use App\Kernel;
use App\Entity\Delegation;
use App\Enum\DelegationStatusEnum;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Dotenv\Dotenv;

require __DIR__.'/vendor/autoload.php';
(new Dotenv())->bootEnv(__DIR__.'/.env');

$kernel = new Kernel('dev', true);
$kernel->boot();

$em = $kernel->getContainer()->get('doctrine')->getManager();
$delegations = $em->getRepository(Delegation::class)->findAll();
$today = (new \DateTimeImmutable())->format('Y-m-d');

echo "Today: $today\n\n";
foreach ($delegations as $d) {
    $startStr = $d->getStartDate()->format('Y-m-d');
    $endStr = $d->getEndDate()->format('Y-m-d');
    $oldStatus = $d->getStatus()->value;
    
    // Replicate calculateDelegationStatus logic
    if ($d->getStatus() !== DelegationStatusEnum::REVOKED) {
        if ($today > $endStr) {
            $d->setStatus(DelegationStatusEnum::EXPIRED);
        } elseif ($today < $startStr) {
            $d->setStatus(DelegationStatusEnum::PENDING);
        } else {
            $d->setStatus(DelegationStatusEnum::ACTIVE);
        }
    }
    $newStatus = $d->getStatus()->value;
    echo "ID: {$d->getId()} | Start: $startStr | End: $endStr | $oldStatus -> $newStatus\n";
}
$em->flush();
echo "\nFlushed to DB.\n";
